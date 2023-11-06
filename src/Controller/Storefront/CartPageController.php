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

use Novalnet\NovalnetSubscription\Helper\Helper;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Content\Product\Exception\ProductNotFoundException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */

class CartPageController extends StorefrontController
{
    /**
     * @var CartService
     */
    private $cartService;
    
    /**
     * @var Helper
     */
    private $helper;

    public function __construct(CartService $cartService, Helper $helper)
    {
        $this->cartService = $cartService;
        $this->helper      = $helper;
    }
    
    /**
     * @Route("/novalnet-subscription/checkout/line-item/add", name="frontend.novalnet-subscription.checkout.line-item.add", methods={"POST"}, defaults={"XmlHttpRequest"=true})
     *
     * @throws MissingRequestParameterException
     * @throws ProductNotFoundException
     */
    public function addLineItems(Cart $cart, RequestDataBag $requestDataBag, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        /** @var RequestDataBag|null $lineItems */
        $lineItems = $requestDataBag->get('lineItems');
        
        if (!$lineItems) {
            throw new MissingRequestParameterException('lineItems');
        }
        $subscriptionData = $requestDataBag->get('subscriptionData');
        
        if (!$subscriptionData) {
            throw new MissingRequestParameterException('subscriptionInterval');
        }
        
        $novalnetSettings = $this->helper->getSubscriptionSettings($salesChannelContext->getSalesChannel()->getId());
        $lineItemId = [];
        
        foreach ($lineItems->all() as $lineItem) {
            $lineItemId = $lineItem['id'];
        }
        
        if (empty($novalnetSettings['NovalnetSubscription.config.mixedCheckout']) && !empty($cart->getLineItems()->getElements())) {
            $formattedLineItemId = str_replace('_', '', $lineItemId);
            foreach ($cart->getLineItems()->getElements() as $currentLineItem) {
                if ($currentLineItem->getId() != $formattedLineItemId) {
                    $this->addFlash(self::INFO, $this->trans('NovalnetSubscription.text.mixedCheckoutNotSupported'));
                    return $this->createActionResponse($request);
                }
            }
        }
        
        $cartPreviousData = [];
        
        if ($cart->hasExtension('novalnetConfiguration')) {
            $cartPreviousData = $cart->getExtension('novalnetConfiguration')->all();
        }
        
        $total = array_merge([
            $lineItemId => $this->getSubscriptionData($subscriptionData, $requestDataBag, $salesChannelContext)
        ], $cartPreviousData);
       
        $cart->addExtension(
            'novalnetConfiguration',
            new ArrayStruct(
                $total
            )
        );
        
        $count = 0;

        try {
            $items = [];
            /** @var RequestDataBag $lineItemData */
            foreach ($lineItems as $lineItemData) {
                $lineItem = new LineItem(
                    $lineItemData->getAlnum('id'),
                    $lineItemData->getAlnum('type'),
                    $lineItemData->get('referencedId'),
                    $lineItemData->getInt('quantity', 1)
                );

                $lineItem->setStackable($lineItemData->getBoolean('stackable', true));
                $lineItem->setRemovable($lineItemData->getBoolean('removable', true));

                $count += $lineItem->getQuantity();

                $items[] = $lineItem;
            }
            
            $cart = $this->cartService->add($cart, $items, $salesChannelContext);
            
            if (!$this->traceErrors($cart)) {
                $this->addFlash(self::SUCCESS, $this->trans('checkout.addToCartSuccess', ['%count%' => $count]));
            }
        } catch (ProductNotFoundException $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.addToCartError'));
        }

        return $this->createActionResponse($request);
    }
    
    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }

        $this->addCartErrorsToFlashBag($cart->getErrors()->getNotices(), 'info');
        $this->addCartErrorsToFlashBag($cart->getErrors()->getWarnings(), 'warning');
        $this->addCartErrorsToFlashBag($cart->getErrors()->getErrors(), 'danger');

        $cart->getErrors()->clear();

        return true;
    }
    
    /**
     * @param Error[] $errors
     */
    private function addCartErrorsToFlashBag(array $errors, string $type): void
    {
        foreach ($errors as $error) {
            $parameters = [];
            foreach ($error->getParameters() as $key => $value) {
                $parameters['%' . $key . '%'] = $value;
            }

            $message = $this->trans('checkout.' . $error->getMessageKey(), $parameters);

            $this->addFlash($type, $message);
        }
    }
    
    /**
     * Return the subscription data
     *
     * @param string $subscriptionData
     * @param RequestDataBag $request
     * @param SalesChannelContext $salesChannelContext
     *
     * @return SalesChannelContext $array
     */
    private function getSubscriptionData(string $subscriptionData, RequestDataBag $request, SalesChannelContext $salesChannelContext): array
    {
        $data = json_decode($subscriptionData, true);
        $subscription = [
            'productId' => $data['productId'],
            'type'      => $data['type'],
            'active'    => $data['active'],
            'interval'  => $data['interval'],
            'period'    => $data['period'],
            'signUpFee' => $data['signUpFee'] * $salesChannelContext->getCurrency()->getFactor(),
            'freeTrial' => $data['freeTrial'],
            'multipleSubscription' => $data['multipleSubscription'],
            'subscriptionLength' => $data['subscriptionLength'],
            'freeTrialPeriod'    => $data['freeTrialPeriod'],
            'detailPageText'     => $data['detailPageText'],
            'discount'=> $data['discount'],
            'discountScope'=> $data['discountScope'],
            'discountType'=> $data['discountType'],
            'discountDetails'=> $data['discountDetails'],
        ];
        
        if (!empty($data['multipleSubscription']) && !empty($request->get('interval'))) {
            list($interval, $period) = $this->helper->getIntervalPeriod($request->get('interval'));
            $subscription['interval'] = $interval;
            $subscription['delivery'] = $request->get('interval');
            $subscription['period'] = $period;
            $subscription['subscriptionLength'] = $interval * $data['operationalMonth'];
        }

        return $subscription;
    }
}
