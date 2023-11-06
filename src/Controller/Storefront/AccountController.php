<?php declare(strict_types=1);

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

namespace Novalnet\NovalnetSubscription\Controller\Storefront;

use Novalnet\NovalnetSubscription\Page\NovalnetServiceLoader;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Account\PaymentMethod\AccountPaymentMethodPageLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */

class AccountController extends StorefrontController
{

    
    /**
     * @var NovalnetServiceLoader
     */
    private $novalnetServiceLoader;
    
    /**
     * @var AccountPaymentMethodPageLoader
     */
    private $orderPageLoader;

    public function __construct(AccountPaymentMethodPageLoader $orderPageLoader, NovalnetServiceLoader $novalnetServiceLoader)
    {
        $this->orderPageLoader          = $orderPageLoader;
        $this->novalnetServiceLoader    = $novalnetServiceLoader;
    }
    
    /**
     * @Route("/account/subscription-orders", name="frontend.novalnet.subscription.orders", methods={"GET"})
     */
    public function subscriptionOrders(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $abos = $this->novalnetServiceLoader->load($request, $salesChannelContext);
        $page = $this->orderPageLoader->load($request, $salesChannelContext);
        $page->assign($abos);
        return $this->renderStorefront('@NovalnetSubscription/storefront/page/account/novalnet-subscription/index.html.twig', ['page' => $page]);
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}", name="frontend.novalnet.subscription.orders.detail", options={"seo"="false"}, methods={"GET"})
     */
    public function orderDetail(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
		if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $abos = $this->novalnetServiceLoader->loadOrderDetails($aboId, $request, $salesChannelContext);
        $page = $this->orderPageLoader->load($request, $salesChannelContext);
        $page->assign($abos);
        return $this->renderStorefront('@NovalnetSubscription/storefront/page/account/novalnet-subscription/detail.html.twig', ['page' => $page]);
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/cancel", name="frontend.novalnet.subscription.cancel", methods={"POST"})
     */
    public function cancel(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
		if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $this->novalnetServiceLoader->cancel($aboId, $salesChannelContext->getContext(), $request);
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.cancelledSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/suspended/cycle", name="frontend.novalnet.subscription.suspended.cycle", methods={"POST"})
     */
    public function suspendedCycle(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
		if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $this->novalnetServiceLoader->pause($aboId, $salesChannelContext->getContext(), $request);
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.suspendedSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/changeQuantity", name="frontend.novalnet.subscription.changeQuantity", methods={"POST"})
     */
    public function changeProductQuantity(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
		if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $this->novalnetServiceLoader->updateProductQuantity($request, $salesChannelContext->getContext());
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.editProductSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/switchSubscription", name="frontend.novalnet.subscription.switchSubscription", methods={"POST"})
     */
    public function switchProduct(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
		if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $this->novalnetServiceLoader->updateProductDetails($request, $salesChannelContext->getContext());
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.switchSubscriptionSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/active/cycle", name="frontend.novalnet.subscription.active.cycle", methods={"POST"})
     */
    public function activeCycle(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
		if (!$salesChannelContext->getCustomer()) {
            return $this->redirectToRoute(
                'frontend.account.login.page',
                ['redirectTo' => 'frontend.novalnet.subscription.orders']
            );
        }
        $this->novalnetServiceLoader->active($aboId, $salesChannelContext->getContext(), $request);
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.reactiveSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/changed", name="frontend.novalnet.subscription.changed", methods={"POST"})
     */
    public function dateChanged(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
        $this->novalnetServiceLoader->cycleDateChange($aboId, $salesChannelContext->getContext(), $request);
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.dateChangedSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
    
    /**
     * @Route("/account/subscription-orders/{aboId}/change-payment", name="frontend.novalnet.subscription.change.payment", options={"seo"="false"}, methods={"POST", "GET"})
     */
    public function changePaymentMethod(string $aboId, Request $request, SalesChannelContext $salesChannelContext)
    {
        $this->novalnetServiceLoader->changePaymentData($aboId, $salesChannelContext->getContext(), $request);
        $this->addFlash('success', $this->trans('NovalnetSubscription.text.changePaymentSuccessful'));
        return $this->redirectToRoute(
            'frontend.novalnet.subscription.orders.detail',
            ['aboId' => $aboId]
        );
    }
}
