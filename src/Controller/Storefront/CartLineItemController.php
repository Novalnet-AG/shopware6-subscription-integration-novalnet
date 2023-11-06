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

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Error\Error;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */

class CartLineItemController extends StorefrontController
{
    
    /**
     * @var CartService
     */
    private $cartService;
    
    /**
     * @var Session
     */
    private $session;
    
    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
        $this->session = new Session();
    }
    
    /**
     * @Route("/checkout/subs-line-item/delete/{id}", name="frontend.novalnet.subscription.line-item.delete", methods={"POST", "DELETE"}, defaults={"XmlHttpRequest": true})
     */
    public function deleteLineItem(Cart $cart, string $id, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        if ($cart->hasExtension('novalnetConfiguration') || !empty($this->session->get('novalnetConfiguration'))) {
            $data = [];
            $novalnetConfiguration = $cart->getExtension('novalnetConfiguration') ? $cart->getExtension('novalnetConfiguration')->all() : $this->session->get('novalnetConfiguration');
            foreach ($novalnetConfiguration as $productId => $values) {
                $lineItemId = str_replace('_', '', $productId);
                
                if ($lineItemId !== $id) {
                    $data[$productId] = $values;
                }
                $cart->addExtension('novalnetConfiguration', new ArrayStruct($data));
                
                if ($this->session->get('novalnetConfiguration')) {
                    $session = $this->session->get('novalnetConfiguration');
                    unset($session[$productId]);
                    $this->session->set('novalnetConfiguration', $session);
                }
            }
        }
        
        try {
            if (!$cart->has($id)) {
                throw CartException::lineItemNotFound($id);
            }

            $cart = $this->cartService->remove($cart, $id, $salesChannelContext);

            if (!$this->traceErrors($cart)) {
                $this->addFlash(self::SUCCESS, $this->trans('checkout.cartUpdateSuccess'));
            }
        } catch (\Exception $exception) {
            $this->addFlash(self::DANGER, $this->trans('error.message-default'));
        }

        return $this->createActionResponse($request);
    }
    
    private function traceErrors(Cart $cart): bool
    {
        if ($cart->getErrors()->count() <= 0) {
            return false;
        }
        
        $this->addCartErrors($cart, fn (Error $error) => $error->isPersistent());

        return true;
    }
}
