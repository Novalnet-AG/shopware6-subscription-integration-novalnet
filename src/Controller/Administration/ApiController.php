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
 * If you wish to customize Novalnet subscription extension for your needs, please contact technic@novalnet.de for more information.
 *
 * @category    Novalnet
 * @package     NovalnetSubscription
 * @copyright   Copyright (c) Novalnet
 * @license     https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */
declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Controller\Administration;

use Novalnet\NovalnetSubscription\Page\NovalnetServiceLoader;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ApiController extends AbstractController
{

    /**
     * @var NovalnetServiceLoader
     */
    private $serviceLoader;
    
    public function __construct(
        NovalnetServiceLoader $serviceLoader
    ) {
        $this->serviceLoader = $serviceLoader;
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/getOrders",
     *     name="api.action.noval.subscription.getOrders",
     *     methods={"POST"}
     * )
     */
    public function getOrders(Request $request, Context $context): JsonResponse
    {
		if (empty($request->get('orderId')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
        $subscriptionOrders = $this->serviceLoader->loadAboFromOrder($request->get('orderId'), $context, $request);
        return new JsonResponse(['success' => true, 'subscriptions' => $subscriptionOrders]);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/fetchSubscription",
     *     name="api.action.noval.subscription.fetchSubscription",
     *     methods={"POST"}
     * )
     */
    public function fetchSubscription(Request $request, Context $context): JsonResponse
    {
		if (empty($request->get('aboId')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
        $subscriptionOrders = $this->serviceLoader->loadAbo($request, $context);
        return new JsonResponse(['success' => true, 'subscriptions' => $subscriptionOrders]);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/pauseSubs",
     *     name="api.action.noval.subscription.pauseSubs",
     *     methods={"POST"}
     * )
     */
    public function pauseAbo(Request $request, Context $context): JsonResponse
    {
		if (empty($request->get('aboId')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
        $this->serviceLoader->pause($request->get('aboId'), $context, $request);
        return new JsonResponse(['success' => true]);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/active",
     *     name="api.action.noval.subscription.active",
     *     methods={"POST"}
     * )
     */
    public function active(Request $request, Context $context): JsonResponse
    {
		if (empty($request->get('aboId')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
        $this->serviceLoader->active($request->get('aboId'), $context, $request);
        return new JsonResponse(['success' => true]);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/cancel",
     *     name="api.action.noval.subscription.cancel",
     *     methods={"POST"}
     * )
     */
    public function cancel(Request $request, Context $context): JsonResponse
    {
		if (empty($request->get('aboId')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
        $this->serviceLoader->cancel($request->get('aboId'), $context, $request);
        return new JsonResponse(['success' => true]);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/renewal",
     *     name="api.action.noval.subscription.renewal",
     *     methods={"POST"}
     * )
     */
    public function renewalSubscription(Request $request, Context $context): JsonResponse
    {
		if (empty($request->get('aboId')) || is_null($request->get('length')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
        return $this->serviceLoader->renewal($request->get('aboId'), $context, $request);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/manualExecution",
     *     name="api.action.noval.subscription.manual.execution",
     *     methods={"POST"}
     * )
     */
    public function manualExecution(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->manualExecutionOrder($request->get('aboId'), $context);
    }
 
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/dateChange",
     *     name="api.action.noval.subscription.date.change",
     *     methods={"POST"}
     * )
     */
    public function dateChange(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->cycleDateChange($request->get('aboId'), $context, $request);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/updateProduct",
     *     name="api.action.noval.subscription.update.product",
     *     methods={"POST"}
     * )
     */
    public function updateProduct(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->updateProductDetails($request, $context);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/updateProductQuantity",
     *     name="api.action.noval.subscription.update.product.quantity",
     *     methods={"POST"}
     * )
     */
    public function updateProductQuantity(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->updateProductQuantity($request, $context);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/NovalnetOrderPaymentName",
     *     name="api.action.noval.subscription.novalnet.order.payment.name",
     *     methods={"POST"}
     * )
     */
    public function getNovalnetChangePaymentData(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->getNovalnetChangePaymentData($request, $context);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/updateSubscriptionProduct",
     *     name="api.action.noval.subscription.update.subscription.product",
     *     methods={"PATCH"}
     * )
     */
     public function updateNovalnetProductConfiguration(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->updateSubscriptionProductConfig($request, $context);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/createSubscriptionProduct",
     *     name="api.action.noval.subscription.create.subscription.product",
     *     methods={"POST"}
     * )
     */
     public function createNovalnetProductConfiguration(Request $request, Context $context): JsonResponse
    {
        return $this->serviceLoader->createSubscriptionProductConfig($request, $context);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/DiscountDelete",
     *     name="api.action.noval.subscription.discount.delete",
     *     methods={"POST"}
     * )
     */
     
     public function discountDelete(Request $request, Context $context): JsonResponse
    {   
	     return $this->serviceLoader->periodDelete($request, $context);
    }
    
    /**
     * @Route(
     *     "/api/_action/novalnet-subscription/updateDiscount",
     *     name="api.action.noval.subscription.update.discount",
     *     methods={"POST"}
     * )
     */
     
     public function updateDiscount(Request $request, Context $context): JsonResponse
    {   
		
	     return $this->serviceLoader->periodUpdated($request, $context);
	   
    }
    
    /**
     * 
     * @Route(
     *     "/api/_action/novalnet-subscription/NovalnetProductDiscount",
     *     name="api.action.noval.subscription.novalnet.product.discount",
     *     methods={"POST"}
     * )
     */
     
     public function novalnetProductsDiscount(Request $request, Context $context): JsonResponse
    {   
		
	     return $this->serviceLoader->discountDetails($request, $context);
	   
    }
}
