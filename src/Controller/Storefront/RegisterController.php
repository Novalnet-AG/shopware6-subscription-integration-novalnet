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

use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\Checkout\Register\CheckoutRegisterPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */

class RegisterController extends StorefrontController
{
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var CheckoutRegisterPageLoader
     */
    private $registerPageLoader;

    public function __construct(CartService $cartService, CheckoutRegisterPageLoader $registerPageLoader)
    {
        $this->cartService = $cartService;
        $this->registerPageLoader = $registerPageLoader;
    }
    
    /**
     * @Route("/novalnet-subscription/checkout/register/{cartId}", name="frontend.novalnet.subscription.checkout.register.page", options={"seo"="false"}, methods={"GET"})
     */
    public function checkoutRegisterPage(string $cartId, Request $request, RequestDataBag $data, SalesChannelContext $context): Response
    {
        if (stripos($cartId, $context->getToken()) === false) {
            throw new UnauthorizedHttpException('trying to access from manually, stopping the process');
        }
        
        /** @var string $redirect */
        $redirect = $request->get('redirectTo', 'frontend.novalnet.subscription.checkout.confirm');
        $redirectParameters = $request->get('redirectParameters', json_encode(['cartId' => $cartId]));
        
        if (\is_string($redirectParameters)) {
            $redirectParameters = json_decode($redirectParameters, true);
        }
        
        if ($context->getCustomer()) {
            return $this->redirectToRoute($redirect, $redirectParameters);
        }
        
        $currentCart = $this->cartService->getCart($cartId, $context);
        
        
        if ($currentCart->getLineItems()->count() === 0) {
            return $this->redirectToRoute('frontend.checkout.cart.page');
        }
        
        $page = $this->registerPageLoader->load($request, $context);
        $page->setCart($currentCart);

        return $this->renderStorefront(
            '@Storefront/storefront/page/checkout/address/index.html.twig',
            [
                'redirectTo' => $redirect,
                'redirectParameters' => json_encode($redirectParameters),
                'errorParameters' => json_encode(['cartId' => $cartId]),
                'page' => $page,
                'data' => $data,
                'isSubsCheckout' => true
            ]
        );
    }
}
