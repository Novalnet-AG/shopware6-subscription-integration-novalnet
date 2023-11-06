<?php
/**
 * Novalnet subscription plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to Novalnet End User License Agreement
 *
 * DISCLAIMER
 *
 * If you wish to customize Novalnet subscription extension for your needs,
 * please contact technic@novalnet.de for more information.
 *
 * @category    Novalnet
 * @package     NovalnetSubscription
 * @copyright   Copyright (c) Novalnet
 * @license     https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Subscriber\Cart;

use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Novalnet\NovalnetSubscription\Components\NovalnetSubscriptionRepository;
use Novalnet\NovalnetSubscription\Components\NovalnetSubsCycleRepository;
use Novalnet\NovalnetSubscription\Content\ProductConfiguration\NovalnetProductConfigurationEntity;
use Novalnet\NovalnetSubscription\Helper\Helper;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Offcanvas\OffcanvasCartPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Uuid\Uuid;

class NovalnetCartLoadedEvent implements EventSubscriberInterface
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
     * @var CartService
     */
    private $cartService;
    
    /**
     * @var EntityRepository
     */
    private $productRepository;
    
    /**
     * @var EntityRepository
     */
    private $orderRepository;
    
    /**
     * @var EntityRepository
     */
    private $orderTransactionRepository;
    
    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        NovalnetSubscriptionRepository $subscriptionRepository,
        NovalnetSubsCycleRepository $subsCycleRepository,
        CartService $cartService,
        EntityRepository $productRepository,
        EntityRepository $orderRepository,
        EntityRepository $orderTransactionRepository,
        Helper $helper
    ) {
        $this->subscriptionRepository   = $subscriptionRepository;
        $this->subsCycleRepository  = $subsCycleRepository;
        $this->cartService          = $cartService;
        $this->productRepository    = $productRepository;
        $this->orderRepository      = $orderRepository;
        $this->helper               = $helper;
        $this->orderTransactionRepository   = $orderTransactionRepository;
    }
    
    /**
     * Register subscribed events
     *
     * return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutRegisterPageLoadedEvent::class => 'addProductToCart',
            CheckoutCartPageLoadedEvent::class => 'addProductToCart',
            OffcanvasCartPageLoadedEvent::class => 'addProductToCart',
            CheckoutConfirmPageLoadedEvent::class => 'addProductToCart',
            CheckoutFinishPageLoadedEvent::class => [
                ['updateSubsFinishCart'],
                ['addCart'],
                ['addSubsToFinishPage'],
            ],
        ];
    }
    
    public function updateSubsFinishCart(CheckoutFinishPageLoadedEvent $event)
    {
        $order          = $event->getPage()->getOrder();
        $orderId        = $event->getPage()->getOrder()->getId();
        $data           = [];
        $salesChannelContext = $event->getSalesChannelContext();
        $transaction    = $order->getTransactions()->last();
        $stateMachine   = $transaction->getStateMachineState();
		
        $this->helper->unsetSession('novalnetConfiguration');
        
        foreach ($order->getLineItems() as $orderLineItem) {
            if ($orderLineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
              
            $data = [
                'orderId'    => $orderId,
                'lineItemId' => $orderLineItem->getId(),
            ];

            $subscriptionEntity = $this->subscriptionRepository->getExistingOrder($salesChannelContext, $data);
            $subscriptionCycleEntity = $this->subsCycleRepository->getExistingCycles($salesChannelContext, ['orderId' => $orderId]);
       
            if ($subscriptionEntity != null) {
				
				$subscriptionCycleEntity = $this->subsCycleRepository->getExistingCycles($salesChannelContext, ['subsId' => $subscriptionEntity->getId()]);
				
				$data['id'] = $subscriptionEntity->getId();
              
                if ($transaction->getPaymentMethodId() != $subscriptionEntity->getPaymentMethodId()) {
                    $data['paymentMethodId'] = $transaction->getPaymentMethodId();
                }
                
                if (in_array($stateMachine->getTechnicalName(), ['cancelled', 'failed'])) {
                    $data['status'] = NovalnetSubscription::SUBSCRIPTION_STATUS_CANCELLED;
                    $data['cancelReason'] = 'Parent order getting failed';
                    $data['cancelledAt'] = date('Y-m-d H:i:s');
                    $cycleStatus    = NovalnetSubscription::CYCLE_STATUS_FAILURE;
                } elseif (in_array($stateMachine->getTechnicalName(), ['open', 'paid_partially', 'paid', 'authorized', 'in_progress', 'reminded'])) {
                    $data['status'] = NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE;
                    $cycleStatus    = NovalnetSubscription::CYCLE_STATUS_SUCCESS;
                }
                
                $this->subscriptionRepository->saveSubscription($salesChannelContext, $data);
                $this->subsCycleRepository->updateCycles($salesChannelContext, [
                    'id'     => $subscriptionCycleEntity->getId(),
                    'status' => $cycleStatus,
                    'cycles' => (is_null($subscriptionEntity->getTrialInterval()) ? 1 : 0),
                ]);
                    
                if (in_array($stateMachine->getTechnicalName(), ['open', 'paid_partially', 'paid', 'authorized', 'in_progress', 'reminded'])) {
                    $this->helper->sendMail($subscriptionEntity->getId(), $salesChannelContext->getContext(), 'novalnet_subs_confirm_mail');
                } else if (in_array($stateMachine->getTechnicalName(), ['cancelled', 'failed'])) {
					$this->helper->sendMail($subscriptionEntity->getId(), $salesChannelContext->getContext(), 'novalnet_cancellation_mail');
				}
            } elseif ($subscriptionCycleEntity != null) {
                if (!in_array($stateMachine->getTechnicalName(), ['open', 'paid_partially', 'paid', 'authorized', 'in_progress', 'reminded'])) {
                    $cycleStatus = NovalnetSubscription::CYCLE_STATUS_RETRY;
                } else {
                    $cycleStatus = NovalnetSubscription::CYCLE_STATUS_SUCCESS;
                }
                
                $this->subsCycleRepository->updateCycles($salesChannelContext, [
                'id'     => $subscriptionCycleEntity->getId(),
                'status' => $cycleStatus
                ]);
                
                $updateParant=$this->subscriptionRepository->getSubOrder($salesChannelContext, $subscriptionCycleEntity->getSubsId());
              
                if ($updateParant) {
                    if ($updateParant->getStatus() == 'SUSPENDED') {
                        $subordercount = $updateParant->get('subsOrders')->count();
                        $count = ((empty($updateParant->getTrialInterval()) || is_null($updateParant->getTrialInterval())) ?  $subordercount :  ($subordercount - 1));
                        $statusUpdate = [ 'id' => $updateParant->getId(), 'status' => $updateParant->getLength() == $count ? NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING_CANCEL : NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE];
                        $this->subscriptionRepository->updateSubscription($salesChannelContext, $statusUpdate);
                        
                        if ($updateParant->getLength() == 0 || $updateParant->getLength() > $count) {
                            $periods = ['d' => 'days', 'w' => 'weeks', 'm' => 'months', 'y' => 'years'];
                            $interval = $subscriptionCycleEntity->getInterval();
                        
                            $subData = [
                                'id' => Uuid::randomHex(),
                                'orderId' => null,
                                'interval' => $interval,
                                'subsId' => $updateParant->getId(),
                                'period' => $subscriptionCycleEntity->getPeriod(),
                                'amount' => $subscriptionCycleEntity->getAmount(),
                                'paymentMethodId' => $updateParant->getPaymentMethodId(),
                                'cycles' => $count + 1,
                                'status' => NovalnetSubscription::CYCLE_STATUS_PENDING,
                                'cycleDate' => $this->helper->getFormattedDate($updateParant->getInterval(), $periods[$updateParant->getUnit()], date('Y-m-d H:i:s'))
                                
                            ];
                            $this->subsCycleRepository->updateCycles($salesChannelContext, $subData);
                        }
                    }
                }
            }
        }
    }
    
    public function addCart(PageLoadedEvent $event)
    {
        $context = $event->getSalesChannelContext();
        $cart = $this->cartService->getCart($context->getToken(), $context);

        if (!empty($cart)) {
            $event->getPage()->addExtension('cart', $cart);
        }
    }
    
    public function addProductToCart(PageLoadedEvent $event)
    {
        if (!($event instanceof OffcanvasCartPageLoadedEvent || $event instanceof CheckoutCartPageLoadedEvent || $event instanceof CheckoutConfirmPageLoadedEvent || $event instanceof CheckoutRegisterPageLoadedEvent)) {
            return;
        } elseif ($this->checkUrl($event->getRequest()->getRequestUri())) {
            return;
        }
        
        $salesChannelContext = $event->getSalesChannelContext();
        $cartService    = $this->cartService->getCart($salesChannelContext->getToken(), $salesChannelContext);
        $page           = $event->getPage();
        
        if ($this->helper->getSession('novalnetConfiguration') && (!$page->getCart()->getExtension('novalnetConfiguration') || !$page->getCart()->getExtension('novalnetConfiguration')->all())) {
            $page->getCart()->addExtension(
                'novalnetConfiguration',
                new ArrayStruct(
                    $this->helper->getSession('novalnetConfiguration')
                )
            );
        }
        
        if (!$page->getCart()->getExtension('novalnetConfiguration') instanceof ArrayStruct) {
            return;
        }
        
        $extensionData = $page->getCart()->getExtension('novalnetConfiguration');
        
        if (!$this->helper->getSession('novalnetConfiguration') && !$event instanceof OffcanvasCartPageLoadedEvent) {
            $this->helper->setSession('novalnetConfiguration', $extensionData->all());
        }
        
        if (count($extensionData->all())) {
            $page->addExtension('novalnetConfiguration', new ArrayStruct([$cartService]));
        }
        
        $this->addNovalnetSubsToCart($page->getCart(), $salesChannelContext);
        
        if ($page->hasExtension('novalnetConfiguration')) {
            if ($event instanceof CheckoutCartPageLoadedEvent) {
                $novalnetSettings = $this->helper->getSubscriptionSettings($salesChannelContext->getSalesChannel()->getId());
                $availablePaymentMethods = [];
                foreach ($page->getPaymentMethods() as $paymentId => $paymentMethod) {
                    if (in_array($paymentId, $novalnetSettings['NovalnetSubscription.config.supportedPayments'])) {
                        $availablePaymentMethods[] = $paymentId;
                    }
                }
                $paymentMethods = $this->helper->getCheckoutPaymentMethods($availablePaymentMethods, $salesChannelContext->getContext());
                $page->setPaymentMethods($paymentMethods);
            }
            $novalnetExtension = $page->getExtension('novalnetConfiguration');
            
            if (!$novalnetExtension instanceof ArrayStruct) {
                return;
            }
            
            foreach ($novalnetExtension->all() as $cart) {
                $this->addNovalnetSubsToCart($cart, $salesChannelContext);
            }
        }
    }
    
    private function checkUrl(string $uri): bool
    {
        if (strpos($uri, '/checkout/confirm') === 0) {
            return true;
        }
        
        return false;
    }
    
    private function addNovalnetSubsToCart(Cart $cart, SalesChannelContext $salesChannelContext): void
    {
        foreach ($cart->getLineItems() as $lineItem) {
            if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
                continue;
            }
            
            /** @var string $productId */
            $productId = $lineItem->getReferencedId();
            $criteria = new Criteria([$productId]);
            $criteria->addAssociation('novalnetConfiguration');

            $product = $this->productRepository->search($criteria, $salesChannelContext->getContext())->first();
            
            if (!$product instanceof ProductEntity) {
                continue;
            }
            
            // get for parent product
            if (!$product->hasExtension('novalnetConfiguration') && $product->getParentId())
            {
				$criteria = new Criteria([$product->getParentId()]);
				$criteria->addAssociation('novalnetConfiguration');

				$product = $this->productRepository->search($criteria, $salesChannelContext->getContext())->first();
			}

            $subscriptionConfiguration = $product->getExtension('novalnetConfiguration');
            
            if (!$subscriptionConfiguration instanceof NovalnetProductConfigurationEntity
                || $subscriptionConfiguration->getType() === 'no_abo' || empty($subscriptionConfiguration->getActive())
            ) {
                continue;
            }
            
            $lineItem->addExtension(
                'novalnetConfiguration',
                new ArrayStruct(
                    [
                        'productId' => $subscriptionConfiguration->getProductId(),
                        'type'      => $subscriptionConfiguration->getType(),
                        'active'    => $subscriptionConfiguration->getActive(),
                        'interval'  => $subscriptionConfiguration->getInterval(),
                        'period'    => $subscriptionConfiguration->getPeriod(),
                        'signUpFee' => $subscriptionConfiguration->getSignUpFee() * $salesChannelContext->getCurrency()->getFactor(),
                        'freeTrial' => $subscriptionConfiguration->getFreeTrial(),
                        'multipleSubscription' => $subscriptionConfiguration->getMultipleSubscription(),
                        'subscriptionLength' => $subscriptionConfiguration->getSubscriptionLength(),
                        'freeTrialPeriod'    => $subscriptionConfiguration->getFreeTrialPeriod(),
                        'detailPageText'     => $subscriptionConfiguration->getDetailPageText(),
                        'discount'     => $subscriptionConfiguration->getDiscount(),
                        'discountScope'     => $subscriptionConfiguration->getDiscountScope(),
                        'discountType'      => $subscriptionConfiguration->getDiscountType(),
                        'discountDetails'      => $subscriptionConfiguration->getDiscountDetails(),
                    ]
                )
            );
        }
    }
    
    public function addSubsToFinishPage(CheckoutFinishPageLoadedEvent $event)
    {
        $context = $event->getContext();
        $orderId = $event->getPage()->getOrder()->getId();

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('subsOrders');
        $criteria->addAssociation('subsOrders.novalnetSubscription');

        $order = $this->orderRepository->search($criteria, $context)->first();
        $subscription = $order->getExtension('subsOrders');
       
        if ($subscription) {
            $event->getPage()->getOrder()->addExtension('subscription', $subscription);
        }
    }
}
