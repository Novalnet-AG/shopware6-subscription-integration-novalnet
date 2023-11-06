<?php
declare(strict_types=1);
/**
 * Novalnet subscription plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet subscription extension for your needs, please contact technic@novalnet.de for more information.
 *
 * @category    Novalnet
 * @package     NovalnetSubscription
 * @copyright   Copyright (c) Novalnet
 * @license     https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

namespace Novalnet\NovalnetSubscription\Controller\Storefront;

use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Novalnet\NovalnetSubscription\Components\NovalnetSubscriptionRepository;
use Novalnet\NovalnetSubscription\Components\NovalnetSubsCycleRepository;
use Novalnet\NovalnetSubscription\Helper\Helper;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\Exception\EmptyCartException;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\Cart\Error\PaymentMethodBlockedError;
use Shopware\Core\Checkout\Payment\Exception\InvalidOrderException;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\UnknownPaymentMethodException;
use Shopware\Core\Checkout\Payment\PaymentService;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\AffiliateTracking\AffiliateTrackingListener;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */

class CheckoutController extends StorefrontController
{
    /**
     * @var NovalnetSubscriptionRepository
     */
    public $subscriptionRepository;
    
    /**
     * @var NovalnetSubsCycleRepository
     */
    public $subsCycleRepository;
    
    /**
     * @var CheckoutConfirmPageLoader
     */
    public $confirmPageLoader;
    
    /**
     * @var CartService
     */
    private $cartService;
    
    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var PaymentService
     */
    private $paymentService;
    
    /**
     * @var Helper
     */
    private $helper;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(
        NovalnetSubscriptionRepository $subscriptionRepository,
        NovalnetSubsCycleRepository $subsCycleRepository,
        CheckoutConfirmPageLoader $confirmPageLoader,
        CartService $cartService,
        OrderService $orderService,
        PaymentService $paymentService,
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->cartService  = $cartService;
        $this->orderService = $orderService;
        $this->paymentService      = $paymentService;
        $this->confirmPageLoader   = $confirmPageLoader;
        $this->subsCycleRepository = $subsCycleRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }
    
    /**
     * @Route("/novalnet-subscription/checkout/confirm/{cartId}", name="frontend.novalnet.subscription.checkout.confirm", options={"seo"="false"}, methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function confirmPage(string $cartId, Request $request, SalesChannelContext $context)
    {
        $customer = $context->getCustomer();
        if (!$customer || $customer->getGuest()) {
            return $this->redirectToRoute(
                'frontend.novalnet.subscription.checkout.register.page',
                ['redirectTo' => 'frontend.novalnet.subscription.checkout.confirm', 'cartId' => $cartId]
            );
        }
        
        if ($this->cartService->getCart($cartId, $context)->getLineItems()->count() === 0) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
        
        $modifiedContext = new SalesChannelContext(
            $context->getContext(),
            $cartId,
            $context->getDomainId(),
            $context->getSalesChannel(),
            $context->getCurrency(),
            $context->getCurrentCustomerGroup(),
            $context->getFallbackCustomerGroup(),
            $context->getTaxRules(),
            $context->getPaymentMethod(),
            $context->getShippingMethod(),
            $context->getShippingLocation(),
            $context->getCustomer(),
            $context->getItemRounding(),
            $context->getTotalRounding(),
            $context->getRuleIds()
        );
        
        $page = $this->confirmPageLoader->load($request, $modifiedContext);
        return $this->renderStorefront('@Storefront/storefront/page/checkout/confirm/index.html.twig', ['page' => $page]);
    }
    
    /**
     * @Route("/novalnet-subscription/checkout/order/{cartId}", name="frontend.novalnet.subscription.checkout.finish.order", options={"seo"="false"}, methods={"POST"})
     */
    public function order(string $cartId, RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        $this->logger->warning('Starting the checkout process!');
        if (!$context->getCustomer()) {
            return $this->redirectToRoute('frontend.checkout.register.page');
        }
        
        $this->helper->setSession('previous_csrf_token', $request->get('_csrf_token'));
       
        if (stripos($cartId, $context->getToken()) === false) {
            $this->logger->warning('trying to access from manually, stopping the process');
            throw new UnauthorizedHttpException('trying to access from manually, stopping the process');
        }
        
        $cart = $this->cartService->getCart($cartId, $context);
        $lineItemAmount = [];
        
        foreach ($cart->getLineItems()->getElements() as $lineItemId => $lineItem) {
            if ($lineItem->hasExtension('novalnetSubsOriginalPrice')) {
                $price  = $lineItem->getExtension('novalnetSubsOriginalPrice')->all();
                $lineItemAmount[$lineItemId] = $price['unitPrice'];
            }
        }
        
        $novalnetConfiguration = [];
        if ($cart->hasExtension('novalnetConfiguration')) {
            $novalnetConfiguration = $cart->getExtension('novalnetConfiguration')->all();
        } elseif ($this->helper->getSession('novalnetConfiguration')) {
            $novalnetConfiguration = $this->helper->getSession('novalnetConfiguration');
        }
      
        $paymentId = $context->getPaymentMethod()->getId();
        $novalnetSettings = $this->helper->getSubscriptionSettings($context->getSalesChannel()->getId());
        
        
        if (!in_array($paymentId, $novalnetSettings['NovalnetSubscription.config.supportedPayments'])) {
            $cart->addErrors(
                new PaymentMethodBlockedError((string) $context->getPaymentMethod()->getTranslation('name'))
            );
        }
        
        $data->set('isSubscriptionOrder', true);
      
        try {
            $this->addAffiliateTracking($data, $request);
            $orderId       = $this->orderService->createOrder($data, $context);
            $orderCriteria = $this->helper->getOrderLineItem($orderId, $context);
            $this->helper->unsetSession('novalnetConfiguration');
            foreach ($orderCriteria->getLineItems()->getElements() as $orderLineItem) {
                if ($orderLineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                    continue;
                }
                
                $lineItemId = $this->helper->getLineItemId($orderLineItem);
                if (in_array($lineItemId, array_keys($novalnetConfiguration))) {
                    if (!empty($lineItemAmount[$orderLineItem->getIdentifier()])) {
                        $amount = $lineItemAmount[$orderLineItem->getIdentifier()] * $orderLineItem->getQuantity();
                    } else {
                        $amount = $orderLineItem->getPrice()->getUnitPrice() * $orderLineItem->getQuantity();
                    }
                    
                    $subcriptionSettings = (array) $novalnetConfiguration[$lineItemId];
                    
                    $discount = 0;
                    $discountType = '';
                    
                    if($subcriptionSettings['discountScope'] == 'cycleduration' && !empty($subcriptionSettings['discountDetails'])){
						$discountPeriod = json_decode($subcriptionSettings['discountDetails'], true);
						foreach($discountPeriod as $period => $periodValues ){
							if($period == $subcriptionSettings['delivery']){
								$discount =  $periodValues['discount'];
								$discountType =  $periodValues['type'];
							}
						}
					}
                    elseif (!empty($subcriptionSettings['discount'])) {
                         $discount = $subcriptionSettings['discount'];
                    }

                    $formattedPeriod = $this->helper->getFormattedPeriod($subcriptionSettings['period']);
                    $length = $subcriptionSettings['subscriptionLength'] == 0 ? $subcriptionSettings['subscriptionLength'] : $subcriptionSettings['subscriptionLength'] / $subcriptionSettings['interval'];
                    
                    $nextDate = $endingDate = date('Y-m-d H:i:s');
                    if (!empty($subcriptionSettings['freeTrial'])) {
                        $nextDate       = $this->helper->getUpdatedNextDate($subcriptionSettings['freeTrial'], $subcriptionSettings['freeTrialPeriod'], date('Y-m-d H:i:s'), null, $context->getContext());
                        $endingDate     = $nextDate;
                    } else {
                        $nextDate       = $this->helper->getUpdatedNextDate($subcriptionSettings['interval'], $subcriptionSettings['period'], date('Y-m-d H:i:s'), null, $context->getContext());
                    }
                    
                    $subsData = [
                        'orderId'    => $orderId,
                        'lineItemId' => $orderLineItem->getId(),
                        'quantity'   => $orderLineItem->getQuantity(),
                        'productId'  => $orderLineItem->getProductId(),
                        'customerId' => $context->getCustomer()->getId(),
                        'interval'   => $subcriptionSettings['interval'],
                        'unit'       => $formattedPeriod,
                        'length'     => $length,
                        'amount'     => $amount,
                        'discount'   => (float) !empty($discount) ? $discount : 0,
                        'discountScope'   => $subcriptionSettings['discountScope'],
                        'discountType'   => !empty($discountType) ? $discountType : $subcriptionSettings['discountType'],
                        'status'     => NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING,
                        'nextDate'   => $nextDate,
                        'endingAt'   => empty($subcriptionSettings['subscriptionLength']) ? null : $this->helper->getUpdatedNextDate($subcriptionSettings['subscriptionLength'], $subcriptionSettings['period'], $endingDate, null, $context->getContext()),
                        'paymentMethodId' => $context->getPaymentMethod()->getId(),
                        'shippingCalculateOnce' => $this->helper->getSubscriptionSettings($context->getSalesChannel()->getId())['NovalnetSubscription.config.calculateShippingOnce']
                    ];

                    $subsCycleData = [
                        'orderId'  => $orderId,
                        'status'   => NovalnetSubscription::CYCLE_STATUS_PENDING,
                        'interval' => $subcriptionSettings['interval'],
                        'period'   => $formattedPeriod,
                        'amount'   => $amount,
                        'paymentMethodId' => $context->getPaymentMethod()->getId(),
                        'cycleDate'=> date('Y-m-d H:i:s')
                    ];
                    
                    if (!empty($subcriptionSettings['freeTrial'])) {
                        $subsData['trialInterval']  = $subcriptionSettings['freeTrial'];
                        $subsData['trialUnit']      = $this->helper->getFormattedPeriod($subcriptionSettings['freeTrialPeriod']);
                    }
                    
                    // Save Subscription data
                    $subsId = $this->subscriptionRepository->saveSubscription($context, $subsData);
                    
                    $subsCycleData['subsId']        = $subsId;
                    
                    // Save Subscription cycle data
                    $this->subsCycleRepository->insertSubscriptionCycles($context, $subsCycleData);
                    
                    $subsCycleData['orderId']       = null;
                    $subsCycleData['cycleDate']     = $this->helper->getFormattedDate($subcriptionSettings['interval'], $subcriptionSettings['period'], date('Y-m-d H:i:s'));
                    
                    // Save Next Subscription cycle data
                    $this->subsCycleRepository->insertSubscriptionCycles($context, $subsCycleData);
                }
            }
        } catch (ConstraintViolationException $formViolations) {
            $this->logger->warning($formViolations);
            return $this->forwardToRoute('frontend.checkout.confirm.page', ['formViolations' => $formViolations]);
        } catch (InvalidCartException | Error | EmptyCartException $error) {
            $this->logger->warning($error->getMessage());
            $this->addCartErrors(
                $this->cartService->getCart($context->getToken(), $context)
            );

            return $this->forwardToRoute('frontend.checkout.confirm.page');
        }

        try {
            $finishUrl = $this->generateUrl('frontend.checkout.finish.page', ['orderId' => $orderId]);
            $errorUrl  = $this->generateUrl(
                'frontend.checkout.finish.page',
                [
                    'orderId' => $orderId,
                    'changedPayment' => false,
                    'paymentFailed' => true,
                ]
            );
            
            $response = $this->paymentService->handlePaymentByOrder($orderId, $data, $context, $finishUrl, $errorUrl);
            
            $this->helper->unsetSession('previous_csrf_token');
            return $response ?? new RedirectResponse($finishUrl);
        } catch (PaymentProcessException | InvalidOrderException | UnknownPaymentMethodException $e) {
            return $this->forwardToRoute('frontend.checkout.finish.page', [
                'orderId'        => $orderId,
                'changedPayment' => false,
                'paymentFailed'  => true
            ]);
        }
    }
    
    private function addAffiliateTracking(RequestDataBag $dataBag, Request $request): void
    {
        $affiliateCode = $request->getSession()->get(AffiliateTrackingListener::AFFILIATE_CODE_KEY);
        $campaignCode  = $request->getSession()->get(AffiliateTrackingListener::CAMPAIGN_CODE_KEY);
        if ($affiliateCode) {
            $dataBag->set(AffiliateTrackingListener::AFFILIATE_CODE_KEY, $affiliateCode);
        }

        if ($campaignCode) {
            $dataBag->set(AffiliateTrackingListener::CAMPAIGN_CODE_KEY, $campaignCode);
        }
    }
}
