<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Controller\Storefront;

use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderException;
use Shopware\Core\Checkout\Order\SalesChannel\AbstractOrderRoute;
use Shopware\Core\Checkout\Order\SalesChannel\AbstractSetPaymentOrderRoute;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Checkout\Payment\Exception\PaymentProcessException;
use Shopware\Core\Checkout\Payment\SalesChannel\AbstractHandlePaymentMethodRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceInterface;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextServiceParameters;
use Shopware\Core\System\SalesChannel\SalesChannel\AbstractContextSwitchRoute;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Event\RouteRequest\SetPaymentOrderRouteRequestEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class AccountOrderController extends StorefrontController
{
    
    
    /**
     * @var OrderService
     */
    public $orderService;
    
    /**
     * @var AbstractOrderRoute
     */
    public $orderRoute;
    
    /**
     * @var AbstractContextSwitchRoute
     */
    public $contextSwitchRoute;
    
    
    /**
     * @var SalesChannelContextServiceInterface
     */
    public $contextService;
    
    /**
     * @var EventDispatcherInterface
     */
    public $eventDispatcher;
    
    /**
     * @var AbstractSetPaymentOrderRoute
     */
    public $setPaymentOrderRoute;
    
    /**
     * @var AbstractHandlePaymentMethodRoute
     */
    public $handlePaymentMethodRoute;
    
    
    public function __construct(
        AbstractOrderRoute $orderRoute,
        OrderService $orderService,
        AbstractContextSwitchRoute $contextSwitchRoute,
        SalesChannelContextServiceInterface $contextService,
        EventDispatcherInterface $eventDispatcher,
        AbstractSetPaymentOrderRoute $setPaymentOrderRoute,
        AbstractHandlePaymentMethodRoute $handlePaymentMethodRoute
    ) {
        $this->orderRoute = $orderRoute;
        $this->orderService = $orderService;
        $this->contextSwitchRoute = $contextSwitchRoute;
        $this->contextService = $contextService;
        $this->eventDispatcher = $eventDispatcher;
        $this->setPaymentOrderRoute = $setPaymentOrderRoute;
        $this->handlePaymentMethodRoute = $handlePaymentMethodRoute;
    }
    
    /**
     * @Route("/novalnet-subscription/account/order/update/{orderId}", name="frontend.novalnet.subscription.account.edit-order.update-order", options={"seo"="false"}, methods={"POST"})
     */

    public function updatesOrder(string $orderId, Request $request, SalesChannelContext $context): Response
    {
        
        $finishUrl = $this->generateUrl('frontend.checkout.finish.page', [
            'orderId' => $orderId,
            'changedPayment' => true,
        ]);

        /** @var OrderEntity|null $order */
        $order = $this->orderRoute->load($request, $context, new Criteria([$orderId]))->getOrders()->first();

        if ($order === null) {
            throw OrderException::orderNotFound($orderId);
        }

        if (!$this->orderService->isPaymentChangeableByTransactionState($order)) {
            throw OrderException::paymentMethodNotChangeable();
        }

        if ($context->getCurrency()->getId() !== $order->getCurrencyId()) {
            $this->contextSwitchRoute->switchContext(
                new RequestDataBag([SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId()]),
                $context
            );

            $context = $this->contextService->get(
                new SalesChannelContextServiceParameters(
                    $context->getSalesChannelId(),
                    $context->getToken(),
                    $context->getContext()->getLanguageId()
                )
            );
        }

        $errorUrl = $this->generateUrl('frontend.account.edit-order.page', ['orderId' => $orderId]);

        $setPaymentRequest = new Request();
        $setPaymentRequest->request->set('orderId', $orderId);
        $setPaymentRequest->request->set('paymentMethodId', $request->get('paymentMethodId'));

        $setPaymentOrderRouteRequestEvent = new SetPaymentOrderRouteRequestEvent($request, $setPaymentRequest, $context);
        $this->eventDispatcher->dispatch($setPaymentOrderRouteRequestEvent);

        $this->setPaymentOrderRoute->setPayment($setPaymentOrderRouteRequestEvent->getStoreApiRequest(), $context);

        $handlePaymentRequest = new Request();
        $handlePaymentRequest->request->set('orderId', $orderId);
        $handlePaymentRequest->request->set('finishUrl', $finishUrl);
        $handlePaymentRequest->request->set('errorUrl', $errorUrl);
        $handlePaymentRequest->request->set('isSubscriptionOrder', true);

        $handlePaymentMethodRouteRequestEvent = new HandlePaymentMethodRouteRequestEvent($request, $handlePaymentRequest, $context);
        $this->eventDispatcher->dispatch($handlePaymentMethodRouteRequestEvent);

        try {
            $routeResponse = $this->handlePaymentMethodRoute->load(
                $handlePaymentMethodRouteRequestEvent->getStoreApiRequest(),
                $context
            );
            $response = $routeResponse->getRedirectResponse();
        } catch (PaymentProcessException) {
            return $this->forwardToRoute(
                'frontend.checkout.finish.page',
                ['orderId' => $orderId, 'changedPayment' => true, 'paymentFailed' => true]
            );
        }

        return $response ?? $this->redirectToRoute(
            'frontend.checkout.finish.page',
            ['orderId' => $orderId, 'changedPayment' => true]
        );
    }
}
