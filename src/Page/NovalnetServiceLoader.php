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

namespace Novalnet\NovalnetSubscription\Page;

use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Novalnet\NovalnetSubscription\Helper\Helper;
use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubscriptionEntity;
use Novalnet\NovalnetSubscription\Content\ProductConfiguration\NovalnetProductConfigurationEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\core\System\SystemConfig\SystemConfigEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Query\ScoreQuery;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\RepositoryIterator;
use Symfony\Contracts\Translation\TranslatorInterface;

class NovalnetServiceLoader
{
    /**
     * @var EntityRepository
     */
    private $subscriptionRepository;
    
    /**
     * @var EntityRepository
     */
    private $subsCycleRepository;
    
    /**
     * @var EntityRepository
     */
    private $novalnetProductRepository;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
   
    /**
     * @var Helper
     */
    private $helper;
    
    /**
     * @var PaymentHandlerRegistry
     */
    private $paymentHandlerRegistry;
    
    /**
     * @var RouterInterface
     */
    private $router;
    
    /**
     * @var TranslatorInterface
     */
    private $translator;
    
    /**
     * @var string
     */
    public $newLine = '/ ';

    public function __construct(
        EntityRepository $subscriptionRepository,
        EntityRepository $subsCycleRepository,
        EntityRepository $novalnetProductRepository,
        LoggerInterface $logger,
        Helper $helper,
        RouterInterface $router,
        PaymentHandlerRegistry $paymentHandlerRegistry,
        TranslatorInterface $translator
    ) {
	    $this->logger     = $logger;
        $this->helper     = $helper;
        $this->router     = $router;
        $this->translator = $translator;
        $this->subscriptionRepository    = $subscriptionRepository;
        $this->subsCycleRepository       = $subsCycleRepository;
        $this->novalnetProductRepository = $novalnetProductRepository;
        $this->paymentHandlerRegistry    = $paymentHandlerRegistry;
    }
    
    
    public function load(Request $request, SalesChannelContext $context): array
    {   
        $customer = $context->getCustomer();
        assert($customer instanceof CustomerEntity);
        
        $limit = (int) $request->query->get('limit', '10');
        $page = (int) $request->query->get('p', '1');
        
        $criteria = new Criteria();
        $criteria->addAssociation('subsOrders');
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));
        $criteria->addFilter(new EqualsFilter('order.salesChannelId', $context->getSalesChannel()->getId()));
        $criteria->addSorting(new FieldSorting('createdAt', 'DESC'));
        $criteria->addSorting(new FieldSorting('subsNumber', 'DESC'));
        $criteria->addSorting(new FieldSorting('subsOrders.createdAt', 'DESC'));
        $criteria->addQuery(new ScoreQuery(new EqualsFilter('subsOrders.status', 'ACTIVE'), 1000));
        $criteria->addQuery(new ScoreQuery(new EqualsFilter('subsOrders.status', 'PENDING'), -1));
        $criteria->addQuery(new ScoreQuery(new EqualsFilter('subsOrders.status', 'SUSPENDED'), -100));
        $criteria->addQuery(new ScoreQuery(new EqualsFilter('subsOrders.status', 'PENDING_CANCEL'), -1000));
        $criteria->addQuery(new ScoreQuery(new EqualsFilter('subsOrders.status', 'CANCELLED'), -100000));
        $criteria->addFilter(new NotFilter('AND', [new EqualsFilter('subsOrders.status', 'INVALID')]));

        $criteria->setLimit($limit)
        ->setOffset(($page - 1) * $limit)
        ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
        
        $abo    = $this->subscriptionRepository->search($criteria, $context->getContext());
        return ['abos' => $abo];
    }
    
    public function loadOrderDetails(string $id, Request $request, SalesChannelContext $context): array
    {   
        $customer = $context->getCustomer();
        assert($customer instanceof CustomerEntity);
        $page = (int) $request->query->get('p', '1');
        $limit = (int) $request->query->get('limit', '5');
        
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('subsOrders');
        $criteria->addAssociation('order');
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.options');
        $criteria->addAssociation('product.options.group');
        $criteria->addAssociation('payment_method');
        $criteria->addAssociation('order.orderCustomer');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.deliveries');
        $criteria->addAssociation('order.paymentMethod');
        $criteria->addAssociation('order.addresses');
        $criteria->addAssociation('order.addresses.country');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('order.lineItems');
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));
        $criteria->addSorting(new FieldSorting('subsOrders.createdAt', 'DESC'));
        $criteria->setLimit(1);
        
        $abo = $this->subscriptionRepository->search($criteria, $context->getContext())->first();
        
        if (!empty($abo) && $abo->get('subsOrders')) {
            $subsCriteria = new Criteria();
            $subsCriteria->addFilter(new EqualsFilter('subsId', $id));
            $subsCriteria->addFilter(new NotFilter('AND', [new EqualsFilter('orderId', null)]));
            $subsCriteria->addAssociation('order');
            $subsCriteria->addAssociation('order.transactions');
            $subsCriteria->addAssociation('order.transactions.paymentMethod');
            $subsCriteria->addSorting(new FieldSorting('createdAt', 'ASC'));
            $subsCriteria->setLimit($limit)
                ->setOffset(($page - 1) * $limit)
                ->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);
                
            $subsCycles = $this->subsCycleRepository->search($subsCriteria, $context->getContext());
            
            $pageCriteria['offset'] = ($page - 1) * $limit;
            $pageCriteria['limit']  = $limit;
			$products = $this->helper->getAllProductDetails($context->getContext());
			$productList = [];
			
			foreach ($products as $product)
			{
				$novalnetConfiguration = $product->getExtension('novalnetConfiguration');
				if ($product->getActive() == true && !empty($novalnetConfiguration) && !empty($novalnetConfiguration->getActive()) && $product->getChildCount() == 0)
                {
					array_push($productList, $product);
				} elseif (!empty($product->getParentId())) {
					$parentProduct = $this->helper->getProduct($product->getParentId(), $context->getContext());
					if ($product->getActive() == true || $parentProduct->getActive() == true)
					{
						$parentNovalnetConfiguration = $parentProduct->getExtension('novalnetConfiguration');
						if ((!empty($novalnetConfiguration) && !empty($novalnetConfiguration->getActive())) || (!empty($parentNovalnetConfiguration) && !empty($parentNovalnetConfiguration->getActive())))
						{
							if (empty($product->getTranslated()['name']))
							{
								$product->setName($parentProduct->getTranslated()['name']);
								$product->SetTranslated(['name' => $parentProduct->getTranslated()['name']]);
							}
							array_push($productList, $product);
						}
					}
				}
			}
			
            return ['abo' => $abo, 'lastExecutedOrders' => $abo->get('subsOrders'), 'entities' => $subsCycles, 'criteria' => $pageCriteria, 'products' => $productList];
        }
        
        return ['abo' => $abo];
    }
    
    public function loadAboFromOrder(string $orderId, Context $context, Request $request)
    {
        $criteria = new Criteria();
        $criteria->addAssociation('novalnetSubscription');
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));
        return $this->subsCycleRepository->search($criteria, $context)->getElements();
    }

    public function loadAbo(Request $request, Context $context)
    {
        $criteria = new Criteria();
        $criteria->addAssociation('subsOrders');
        $criteria->addAssociation('order');
        $criteria->addFilter(new EqualsFilter('id', $request->get('aboId')));
        return $this->subscriptionRepository->search($criteria, $context)->getElements();
    }
    
    public function cancel(string $id, Context $context, Request $request)
    {	
		$id = strtolower($id);
		$customerId = !empty($request->get('customerId')) ? strtolower($request->get('customerId')) : NULL;
        $this->subscriptionRepository->upsert(
            [
                [
                    'id'           => $id,
                    'status'       => NovalnetSubscription::SUBSCRIPTION_STATUS_CANCELLED,
                    'cancelledAt'  => date('Y-m-d H:i:s'),
                    'cancelReason' => $request->get('cancelReason'),
                    'canceledBy'   => $customerId
                ]
            ],
            $context
        );
        $this->logger->warning('Subscription ID {subscriptionId} cancelled successfully', ['subscriptionId' => $id]);
        $this->helper->sendMail($id, $context, 'novalnet_cancellation_mail');
    }
    
    public function pause(string $id, Context $context, Request $request)
    {	
		$id = strtolower($id);
		$customerId = !empty($request->get('customerId')) ? strtolower($request->get('customerId')) : NULL;
        $this->subscriptionRepository->upsert(
            [
                [
                    'id'           => $id,
                    'status'       => NovalnetSubscription::SUBSCRIPTION_STATUS_SUSPENDED,
                    'terminationDate'  => date('Y-m-d H:i:s'),
                    'cancelReason' => $request->get('cancelReason'),
                    'canceledBy'   => $customerId
                ]
            ],
            $context
        );
        $this->logger->warning('Subscription ID {subscriptionId} suspended successfully', ['subscriptionId' => $id]);
        $this->helper->sendMail($id, $context, 'novalnet_suspend_mail');
    }
    
    public function active(string $id, Context $context, Request $request)
    {	
		$id = strtolower($id);
		
        $this->subscriptionRepository->upsert(
            [
                [
                    'id'           => $id,
                    'status'       => NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE,
                ]
            ],
            $context
        );
        $this->logger->warning('Subscription ID {subscriptionId} reactivate successfully', ['subscriptionId' => $id]);
        $this->helper->sendMail($id, $context, 'novalnet_reactive_mail');
    }

    public function renewal(string $id, Context $context, Request $request): JsonResponse
    {
		if (!empty($request->get('length')) && gettype($request->get('length')) != 'integer')
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `quantity` excepted data type - integer']);
		}
		
		$id = strtolower($id);
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('subsOrders');
        $criteria->addSorting(new FieldSorting('subsOrders.createdAt', 'DESC'));
        $abo    = $this->subscriptionRepository->search($criteria, $context)->first();
        
        if (!$abo instanceof NovalnetSubscriptionEntity) {
            return new JsonResponse(['success' => false , 'errorMessage' => 'Subscription is not available']);
        } elseif ($abo->getStatus() != NovalnetSubscription::SUBSCRIPTION_STATUS_EXPIRED) {
			return new JsonResponse(['success' => false, 'errorMessage' => 'The subscription has not expired. So, not able to run renewal execution.']);
		}
        $length = ($request->get('length') == 0) ? $request->get('length') : ($request->get('length') + $abo->getLength());
        $unit   = ($abo->getUnit() == 'd') ? 'days' : ($abo->getUnit() == 'w' ? 'weeks' : ($abo->getUnit() == 'm' ? 'months' : 'years'));
        
        $data = [
            'id'           => $id,
            'status'       => NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE,
            'nextDate'     => $this->helper->getFormattedDate(0, $unit, date('Y-m-d H:i:s')),
            'endingAt'     => $this->helper->getFormattedDate((int) ($request->get('length') * $abo->getInterval()), $unit, date('Y-m-d H:i:s')),
            'length'       => $length,
        ];
        
        $subsCycleData = [
            'id'       => Uuid::randomHex(),
            'subsId'   => $id,
            'orderId'  => null,
            'status'   => NovalnetSubscription::CYCLE_STATUS_PENDING,
            'interval' => $abo->getInterval(),
            'period'   => $abo->getUnit(),
            'amount'   => $abo->getAmount(),
            'paymentMethodId' => $abo->getPaymentMethodId(),
            'cycleDate'=> date('Y-m-d H:i:s')
        ];
        
        // Save Subscription cycle data
        $this->subsCycleRepository->upsert([$subsCycleData], $context);
        
        $this->subscriptionRepository->upsert([$data], $context);
        
        $this->logger->warning('Subscription ID {subscriptionId} renewal successfully', ['subscriptionId' => $id]);
        $this->helper->sendMail($id, $context, 'novalnet_renewal_mail');
        return new JsonResponse(['success' => true]);
    }
    
    public function manualExecutionOrder(string $id = null, Context $context): JsonResponse
    {
		if (empty($id))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
		$id = strtolower($id);
        $criteria = new Criteria([$id]);
        $criteria->addAssociation('subsOrders');
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.transactions');
        $criteria->addAssociation('order.salesChannel');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('payment_method');
        $criteria->addSorting(new FieldSorting('subsOrders.createdAt', 'DESC'));
        $subscription = $this->subscriptionRepository->search($criteria, $context)->first();
        
        if (!$subscription instanceof NovalnetSubscriptionEntity) {
            return new JsonResponse(['success' => false , 'errorMessage' => 'Subscription is not available']);
        } elseif ($subscription->getStatus() != NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE) {
			return new JsonResponse(['success' => false, 'errorMessage' => 'The subscription is not active. So, not able to run manual execution.']);
		}

        $subsOrders         = $subscription->get('subsOrders');
        $paymentMethod      = $subscription->get('payment_method');
        $orderCustomer      = $subscription->get('order')->getOrderCustomer();
        $salesChannelEntity = $subscription->get('order')->getSalesChannel();
        
        if (empty($paymentMethod->getActive())) {
            $paymentHandler = $this->helper->getNovalnetPaymentHandler($paymentMethod->getHandlerIdentifier());
            if (in_array($paymentHandler, ['NovalnetInvoiceGuarantee', 'NovalnetSepa', 'NovalnetApplePay', 'NovalnetPrepayment', 'NovalnetSepaGuarantee', 'NovalnetInvoice', 'NovalnetGooglePay', 'NovalnetCreditCard'])) {
                $novalnetPaymentmethod = $this->helper->getPaymentMethod();
                if (!empty($novalnetPaymentmethod)) {
                    $paymentMethod = $novalnetPaymentmethod;
                    $subscription->setPaymentMethodId($paymentMethod->getId());
                }
            }
        }

        $orderTransaction    = $this->helper->getLastOrderTransaction($subscription->get('order')->getId(), $orderCustomer->getCustomerId(), $context);
        $salesChannelContext = $this->helper->getSalesChannelContext($subscription, $salesChannelEntity, $orderTransaction);
        
        if (empty($orderTransaction)) {
            $this->logger->warning('no order transaction could be found for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId()]);
            return new JsonResponse(['success' => false, 'errorMessage' => 'no order transaction could be found for subscription']);
        }
        
        $lastAboCycleData = null;
        foreach ($subsOrders as $abo) {
            if (is_null($abo->getOrderId()) || $abo->getStatus() == NovalnetSubscription::CYCLE_STATUS_RETRY || $abo->getStatus() == NovalnetSubscription::CYCLE_STATUS_FAILURE) {
                $lastAboCycleData = $abo;
            }
        }
        
        $count = ((empty($subscription->getTrialInterval()) || is_null($subscription->getTrialInterval())) ? (int) $subsOrders->count() : (int) ($subsOrders->count() - 1));
        if (is_null($lastAboCycleData) && !empty($subscription->getLength()) && ($subscription->getLength() == $count || $count > $subscription->getLength())) {
            $this->subscriptionRepository->upsert([[
                'id' => $subscription->getId(),
                'status' => NovalnetSubscription::SUBSCRIPTION_STATUS_EXPIRED,
                ]], $salesChannelContext->getContext());
            $this->logger->warning('All recurring order is completed for the subscription {subscriptionId}, So subscription is expired', ['subscriptionId' => $subscription->getId()]);
            return new JsonResponse(['success' => false, 'errorMessage' => 'All recurring order is completed for the subscription '. $subscription->getId() .', So subscription is expired']);
        } else {
            if (!empty($paymentMethod) && $paymentMethod->getActive() == 1) {
                try {
                    $this->logger->debug('start creating new order for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId()]);
                    $createdOrder = $this->helper->createNewOrder($subscription, $orderTransaction, $salesChannelContext, $paymentMethod);
                     
                    if (!$createdOrder) {
                        $this->logger->warning('Recurring order not completed for the subscription {subscriptionId}', ['subscriptionId' => $subscription->getId()]);
                        return new JsonResponse(['success' => false, 'errorMessage' => 'Recurring order not completed for the subscription '. $subscription->getId()]);
                    }
                    $createdOrderTransactions = $createdOrder->getTransactions();
                    assert($createdOrderTransactions instanceof OrderTransactionCollection);
                    $latestTransaction = $createdOrderTransactions->last();
                    if (!$latestTransaction instanceof OrderTransactionEntity) {
						$this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, 'something went wrong here. there is no order transaction is found.');
                        return new JsonResponse(['success' => false, 'errorMessage' => 'something went wrong here. there is no order transaction is found.']);
                    }
                    
                    if (!empty($lastAboCycleData)) {
                        $periods = ['d' => 'days', 'w' => 'weeks', 'm' => 'months', 'y' => 'years'];
                        $status  = NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING_CANCEL;
                        $paymentHandler = $this->paymentHandlerRegistry->getPaymentMethodHandler($paymentMethod->getId());

                        if (method_exists($paymentHandler, 'recurring')) {
                            if ($paymentHandler instanceof SynchronousPaymentHandlerInterface) {
                                $paymentTransaction = new SyncPaymentTransactionStruct($latestTransaction, $createdOrder);
                            } else {
                                $parameter = ['_sw_payment_token' => $salesChannelContext->getToken()];
                                $returnUrl = $this->router->generate('payment.finalize.transaction', $parameter, UrlGeneratorInterface::ABSOLUTE_URL);
                                $paymentTransaction = new AsyncPaymentTransactionStruct($latestTransaction, $createdOrder, $returnUrl);
                            }
                            $dataBag = new RequestDataBag();
                            $dataBag->set('isRecurringOrder', true);
                            $this->helper->setSession('novalnetSubscriptionParentOrder', $subscription->get('order')->getOrderNumber());
                            try {
                                if ($paymentHandler->recurring($paymentTransaction, $dataBag, $salesChannelContext)) {
                                    $cycleStatus = NovalnetSubscription::CYCLE_STATUS_SUCCESS;
                                } else {
                                    $this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, 'Payment failed with the service provider.', $createdOrder->getId());
									$this->logger->notice('Payment failed with the service provider for this recurring {subscriptionId}.', ['subscriptionId' => $subscription->getId()]);
									return new JsonResponse(['success' => false, 'errorMessage' => 'Payment failed with the service provider for this recurring ' . $subscription->getId()]);
                                }
                            } catch (\Exception $e) {
                                $this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, $e->getMessage());
                                $this->logger->emergency('could not create a order for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId(), 'cartErrors' => $e->getMessage()]);
                                return new JsonResponse(['success' => false, 'errorMessage' => 'could not create a order for subscription ' . $subscription->getId()]);
                            }
                            $this->helper->unsetSession('novalnetSubscriptionParentOrder');
                        } else {
                            $cycleStatus = NovalnetSubscription::CYCLE_STATUS_SUCCESS;
                        }
                        
                        $data = [
                            'id'            => $lastAboCycleData->getId(),
                            'subsId'        => $subscription->getId(),
                            'orderId'       => $createdOrder->getId(),
                            'interval'      => $subscription->getInterval(),
                            'period'        => $subscription->getUnit(),
                            'amount'        => $subscription->getAmount(),
                            'status'        => $cycleStatus,
                            'paymentMethodId' => $paymentMethod->getId(),
                            'cycles'        => $count,
                            'cycleDate'     => date('Y-m-d H:i:s')
                        ];
                        
                        // Update Subscription cycle data
                        $this->subsCycleRepository->upsert([$data], $context);
                        
                        if ($subscription->getLength() == 0 || $subscription->getLength() > $count && $cycleStatus == NovalnetSubscription::CYCLE_STATUS_SUCCESS) {
                            $data ['id'] = Uuid::randomHex();
                            $data['orderId']    = null;
                            $data['cycles']     = $count + 1;
                            $data['status']     = NovalnetSubscription::CYCLE_STATUS_PENDING;
                            $data['cycleDate']  = $this->helper->getFormattedDate($subscription->getInterval(), $periods[$subscription->getUnit()], date('Y-m-d H:i:s'));
                        
                            // Save Next Subscription cycle data
                            $this->subsCycleRepository->upsert([$data], $context);
                            $status = NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE;
                        }
                        
                        // Update next cycle date in subscription tabel
                        $this->subscriptionRepository->upsert([[
                            'id'    => $subscription->getId(),
                            'status'    => $status,
                            'nextDate'  => $this->helper->getUpdatedNextDate($subscription->getInterval(), $periods[$subscription->getUnit()], $subscription->getNextDate()->format('Y-m-d H:i:s'), $subscription, $context)
                        ]], $salesChannelContext->getContext());
                        
                        
                        if ($status == NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING_CANCEL) {
                            $this->helper->sendRenewalReminder($subscription, $context);
                        }
                    } else {
                        $this->logger->debug('subscription cycle data is already updated.');
                        $this->logger->debug('there is no entries to update the cycles.');
                    }
                    
                    return new JsonResponse(['success' => true]);
                } catch (\Exception $e) {
					
					$errorMessage = (method_exists($e, 'getParameters') && !empty($e->getParameters())) ? $e->getParameters()['errors'] : $e->getMessage();
					
					$errorMessage = explode(':', $errorMessage);
					$errorMessage = trim(end($errorMessage));
					
                    $data = [];
                    
                    $subject = 'Not able to create recurring order for the subscription number '. $subscription->getSubsNumber();
                    
                    $message = '<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px;"> Dear Shop Owner,<br/><br/>';
                    
                    $message .= 'We are extremely sorry to inform you that the subscription with order number '.  $subscription->getSubsNumber() .' could not be executed for the following reason: '. $errorMessage;
                    
                    $message .= '<br/><br/>We regret the inconvenience caused to you.';
                    
                    $message .= '<br/><br/>Note: Do not reply to this E-mail. This e-mail was automatically generated from the '. $salesChannelContext->getSalesChannel()->getName() .' website.';
                    
                    $message .= '<br/><br/>Regards<br/>Novalnet Team';
                    
                    $message .= '</div>';
                    
                    $this->helper->sendNotificationMail($salesChannelContext, $message, $subject);
                    $this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, $errorMessage);
                    $this->logger->emergency('could not create a order for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId(), 'cartErrors' => $errorMessage]);
                    return new JsonResponse(['success' => false, 'errorMessage' => $errorMessage]);
                }
            } else {
				$this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, 'Payment method is not available/not active for this recurring');
                $this->logger->notice('Payment method is not available/not active for this recurring {subscriptionId}.', ['subscriptionId' => $subscription->getId()]);
                return new JsonResponse(['success' => false, 'errorMessage' => 'Payment method is not available/not active for this recurring']);
            }
        }
    }

    public function cycleDateChange(string $id, Context $context, Request $request) :JsonResponse
    {
		if (empty($request->get('aboId')) || empty($request->get('nextDate')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
        try {
			$id = strtolower($id);
            $criteria = new Criteria([$id]);
            $criteria->addAssociation('subsOrders');
            $criteria->addSorting(new FieldSorting('subsOrders.createdAt', 'DESC'));
            $abo    = $this->subscriptionRepository->search($criteria, $context)->first();
            
            if (!$abo instanceof NovalnetSubscriptionEntity) {
				return new JsonResponse(['success' => false , 'errorMessage' => 'Subscription is not available']);
			} elseif ($abo->getStatus() != NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE) {
				return new JsonResponse(['success' => false, 'errorMessage' => 'The subscription is not active. So, not able to change the cycle date.']);
			}
		
            $dateChangeReason =  $abo->getDateChangeReason();
            $dateChangeReason .= '/ '. $request->get('reason');
            $subcyclecount = $abo->get('subsOrders')->count();
            $count = $subcyclecount - 1;
            $length =  (($abo->getLength() == 0) ? $abo->getLength() : $abo->getLength() - $count);
            if (!empty($abo->getTrialInterval()))
            {
				$length += 1; 
			}
			$length = $length * $abo->getInterval();
            $unit   = ($abo->getUnit() == 'd') ? 'days' : ($abo->getUnit() == 'w' ? 'weeks' : ($abo->getUnit() == 'm' ? 'months' : 'years'));
            $nextDate = $this->helper->getFormattedDate(0, $unit, $request->get('nextDate'));
            $today =  date('Y-m-d H:i:s');
            
            if ($today > $nextDate) {
                return new JsonResponse(['errorMessage' => $this->translator->trans('NovalnetSubscription.text.invalidDate')]);
            }
            
            $this->subscriptionRepository->upsert([[
				'id'           => $id,
				'nextDate'     => $nextDate,
				'lastDayMonth' => false,
				'endingAt'     => $this->helper->getFormattedDate($length, $unit, $nextDate),
				'dateChangeReason' => $dateChangeReason,
            ]], $context);
            
           $this->helper->sendMail($id, $context, 'novalnet_remaing_cycle_date_change_mail');
           return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false]);
        }
    }
    
    public function changeSubscriptionLength(Context $context, Request $request)
    {
		if (empty($request->get('aboId')) || empty($request->get('length')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
		$aboId = strtolower($request->get('aboId'));
		
        $this->subscriptionRepository->update(
            [
                [
                    'id'       => $aboId,
                    'length' => (int) $request->get('length'),
                ]
            ],
            $context
        );
	}
    
    public function changePaymentData(string $id, Context $context, Request $request)
    {
		$this->subscriptionRepository->upsert(
            [
                [
                    'id'           => $id,
                    'paymentMethodId' => $request->get('paymentMethodId'),
                ]
            ],
            $context
        );
        
		$criteria = new Criteria([$id]);
        $criteria->addAssociation('payment_method');
        $criteria->addAssociation('order');
        $abo = $this->subscriptionRepository->search($criteria, $context)->first();
        $currentPaymentName = '';
        $orderNumber = !empty($abo->get('order')->getOrderNumber()) ? $abo->get('order')->getOrderNumber() : '';
        
        if(!empty($abo->get('payment_method')) && !empty($abo->get('payment_method')->getCustomFields()) && !empty($abo->get('payment_method')->getCustomFields()['novalnet_payment_method_name']) && !empty($abo->get('payment_method')->getCustomFields()['novalnet_payment_method_name'] == 'novalnetpay')){
			if(!empty($orderNumber)){
				$currentName = $this->helper->getNovalnetPaymentName($context, $orderNumber);
				if(!empty($currentName)){
					$currentPaymentName = $currentName;
				}
				else {
					$currentPaymentName = !empty($abo->get('payment_method')->getTranslated()['name']) ? $abo->get('payment_method')->getTranslated()['name'] : $abo->get('payment_method')->getName();
				}
			} else {
				$currentPaymentName = !empty($abo->get('payment_method')->getTranslated()['name']) ? $abo->get('payment_method')->getTranslated()['name'] : $abo->get('payment_method')->getName();
			}
			
		} else {
			$currentPaymentName = !empty($abo->get('payment_method')->getTranslated()['name']) ? $abo->get('payment_method')->getTranslated()['name'] : $abo->get('payment_method')->getName();
		}
        
        $this->logger->warning('Subscription ID {subscriptionId} payment method updated successfully', ['subscriptionId' => $id]);
        $this->helper->sendMail($id, $context, 'novalnet_payment_change_mail', $currentPaymentName);
    }
    
    public function updateProductDetails(Request $request, Context $context) :JsonResponse
    {	
		$quantity = (int) $request->get('quantity');
		
		if (empty($request->get('aboId')) || empty($request->get('productId')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
		if (!empty($quantity) && gettype($quantity) != 'integer')
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `quantity` excepted data type - integer']);
		}
		
		$aboId = strtolower($request->get('aboId'));
		$productId = strtolower($request->get('productId'));
		$criteria = new Criteria([$aboId]);
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.options');
        $criteria->addAssociation('product.options.group');
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.lineItems');
        $abo = $this->subscriptionRepository->search($criteria, $context)->first();
        $previousProductName = '';
        
        if ($request->get('productId') == $abo->getProductId() && !empty($quantity) && $quantity != $abo->getQuantity())
        {
			return $this->updateProductQuantity($request, $context);
		} elseif ($request->get('productId') == $abo->getProductId() && $quantity == $abo->getQuantity()) {
			return new JsonResponse(['success' => false, 'errorMessage' => 'There is no changes in the product variant and quantity']);
		}
		
        if ($abo->get('product'))
        {
			if (empty($abo->get('product')->getTranslated()['name']) && empty($abo->get('product')->getName()))
			{
				$product = $this->helper->getParentProductId($abo->get('product')->getParentId(), $context);
				$previousProductName = !empty($product->getTranslated()['name']) ? $product->getTranslated()['name'] : $product->getName();
			} else {
				$previousProductName = !empty($abo->get('product')->getTranslated()['name']) ? $abo->get('product')->getTranslated()['name'] : $abo->get('product')->getName();
			}
			
			if (!empty($abo->get('product')->getOptions()->getElements()))
			{
				$previousProductName .= ' ( ';
				foreach ($abo->get('product')->getOptions()->getElements() as $element) {
					$previousProductName .= (!empty($element->getGroup()->getTranslated()['name']) ? $element->getGroup()->getTranslated()['name'] : $element->getGroup()->getName()) . ': ';
					$previousProductName .= (!empty($element->getTranslated()['name']) ? $element->getTranslated()['name'] : $element->getGroup()->getName()) . ' | ';
				}
				
				$previousProductName = substr($previousProductName, 0, -3);
				$previousProductName .= ' )';
			}
		} else {
			foreach ($abo->get('order')->getLineItems()->getElements() as $lineItem)
			{
				if ($lineItem->getId() == $abo->getLineItemId())
				{
					$previousProductName = $lineItem->getLabel();
					if (!empty($lineItem->getPayload()['options']))
					{
						$previousProductName .= ' ( ';
						foreach ($lineItem->getPayload()['options'] as $option)
						{
							$previousProductName .= $option['group'] . ': ' . $option['option'] . ' | ';
						}
						$previousProductName = substr($previousProductName, 0, -3);
						$previousProductName .= ' )';
					}
				}
			}
		}
		$productConfig =  $this->helper->getProduct($productId, $context);
		$data = $details = [];
		$data = [
			'id'        => $aboId,
            'productId' => $productId,
            'quantity'  => !empty($quantity) ? $quantity : $abo->getQuantity()
		];
		
		if (!empty($productConfig->getExtension('novalnetConfiguration')))
		{
			$novalnetConfiguration = $productConfig->getExtension('novalnetConfiguration');
			
			if($novalnetConfiguration->getDiscountScope() !='cycleduration'){
				$data ['discount']      = $novalnetConfiguration->getDiscount();
				$data ['discountType']  = $novalnetConfiguration->getDiscountType();
				$data ['discountScope'] = $novalnetConfiguration->getDiscountScope();
		    } else {
				
				$details = $this->cycleDurationDiscount($abo, $novalnetConfiguration);
			}
		} elseif (empty($productConfig->getExtension('novalnetConfiguration')) && $productConfig->getParentId()) {
			$productConfig =  $this->helper->getProduct($productConfig->getParentId(), $context);
			$novalnetConfiguration = $productConfig->getExtension('novalnetConfiguration');
			if (!is_null($novalnetConfiguration))
			{
				if($novalnetConfiguration->getDiscountScope() !='cycleduration'){
					$data ['discount']      = $novalnetConfiguration->getDiscount();
					$data ['discountType']  = $novalnetConfiguration->getDiscountType();
					$data ['discountScope'] = $novalnetConfiguration->getDiscountScope();
				} else {
					$details = $this->cycleDurationDiscount($abo, $novalnetConfiguration);
					
				}
			}
		}
		
		$data = (array_merge($data, $details));
        $this->subscriptionRepository->upsert([$data],$context);
        $this->logger->warning('Product details updated successfully for Subscription ID {subscriptionId}', ['subscriptionId' => $request->get('aboId')]);
        $this->helper->sendMail($request->get('aboId'), $context, 'novalnet_product_change_mail', $previousProductName);
        return new JsonResponse(['success' => true]);
    }
    
    public function updateProductQuantity(Request $request, Context $context) :JsonResponse
    {	
		$quantity = (int) $request->get('quantity');
		
		if (empty($request->get('aboId')) || empty($request->get('quantity')))
        {
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
		if (gettype($quantity) != 'integer')
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `quantity` excepted data type - integer']);
		}
		
		$aboId = strtolower($request->get('aboId'));
		$criteria = new Criteria([$aboId]);
        $abo = $this->subscriptionRepository->search($criteria, $context)->first();
		
		if (empty($abo))
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Not able to find the subscription for the id: '. $aboId]);
		}
		
		if ($abo->getQuantity() == $quantity)
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'There is no changes in the product quantity']);
		}
		
        $this->subscriptionRepository->update(
            [
                [
                    'id'       => $aboId,
                    'quantity' => $quantity,
                ]
            ],
            $context
        );
        
        if ($abo->getQuantity() > $request->get('quantity'))
        {
			$this->logger->warning('Product quantity downgrade successfully for Subscription ID {subscriptionId}', ['subscriptionId' => $aboId]);
			$this->helper->sendMail($aboId, $context, 'novalnet_product_downgrade_mail');
		} else {
			$this->logger->warning('Product quantity upgrade successfully for Subscription ID {subscriptionId}', ['subscriptionId' => $aboId]);
			$this->helper->sendMail($aboId, $context, 'novalnet_product_upgrade_mail');
		}
        return new JsonResponse(['success' => true]);
    }
    
    public function getNovalnetChangePaymentData(Request $request, Context $context) :JsonResponse 
    {
		if(!empty($request->get('orderNumber'))){
			$paymentName = $this->helper->getNovalnetPaymentName($context, $request->get('orderNumber'));
			if(!empty($paymentName)){
				return new JsonResponse(['paymentName' => $paymentName]);
			}
		}
		return new JsonResponse(['paymentName' => '']);
	}
	
	
	public function updateSubscriptionProductConfig(Request $request, Context $context): JsonResponse 
    {
		if (empty($request->get('id')) && empty($request->get('productId')))
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
		$criteria = new Criteria();
		if (!empty($request->get('id')))
		{
			$criteria->addFilter(new EqualsFilter('id', $request->get('id')));
		} else {
			$criteria->addFilter(new EqualsFilter('productId', $request->get('productId')));
		}
		$subscriptionDetails =  $this->novalnetProductRepository->search($criteria, $context);
		
		if (empty($subscriptionDetails))
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Product Details not founded in the subscription table for the ID - ' . $request->get('id')]);
		}
		
		$subscriptionDetails = $subscriptionDetails->first();
		$id                  = strtolower($subscriptionDetails->getId());
		$product             = $this->helper->getProduct($subscriptionDetails->getProductId(), $context);
		$price               = $product->getPrice()->first();
		$dataPresent = false; $data ['id'] = $id;
		$dataType = ['boolean' => ['active', 'multipleSubscription'], 'integer' => ['interval', 'subscriptionLength', 'freeTrial', 'operationalMonth']];
		
		foreach (['active', 'type', 'interval', 'period', 'subscriptionLength', 'signUpFee', 'freeTrial', 'freeTrialPeriod', 'multipleSubscription', 'multiSubscriptionOptions', 'operationalMonth', 'discount'
		, 'discountScope', 'discountType', 'detailPageText', 'predefinedSelection', 'productId'] as $key)
		{
			if (!is_null($request->get($key)))
			{
				$data [$key] = $request->get($key);
				
				foreach ($dataType as $dataTypeKey => $dataTypeValue)
				{
					if (in_array($key, $dataTypeValue) && gettype($request->get($key)) != $dataTypeKey)
					{
						return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `'. $key . '` excepted data type - '.  $dataTypeKey]);
					}
				}
				
				if (($key == 'discount' || $key == 'signUpFee') && !in_array(gettype($request->get($key)), ['double', 'integer']))
				{
					return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `'. $key . '` excepted data type - double (or) integer']);
				}
				$dataPresent = true;
			}
		}
		
		if (isset($data['productId']))
		{
			$data['productId'] = strtolower($data['productId']);
		}
		
		if (!$dataPresent)
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'JSON data is empty']);
		}
		
		if (isset($data ['subscriptionLength']))
		{
			$data ['subscriptionLength'] = !empty($data ['interval']) ? $data ['interval'] * $data ['subscriptionLength'] : $subscriptionDetails->getInterval() * $data ['subscriptionLength'];
		}
		
		if (!empty($data['discountType']) && $data['discountType'] == 'percentage' && $data['discount'] > 100)
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Discount should be lesser than or equal to 100 percentage']);
		} elseif (!empty($data['discountType']) && $data['discountType'] == 'fixed' && $data['discount'] > $price->getGross())
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Discount should be lesser than or equal to product price amount']);
		}
		
		$this->novalnetProductRepository->upsert([$data], $context);
						
		return new JsonResponse(['success' => true]);
	}
	
	
	public function createSubscriptionProductConfig(Request $request, Context $context) :JsonResponse 
    {
		if (empty($request->get('productId')) || empty($request->get('active')))
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Mandaory params is missing']);
		}
		
		$criteria = new Criteria();
		$criteria->addFilter(new EqualsFilter('productId', $request->get('productId')));
		$subscriptionDetails =  $this->novalnetProductRepository->search($criteria, $context)->first();
		
		if (!is_null($subscriptionDetails))
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Subscription entry is already created for this Product ID - '. $request->get('productId')]);
		}
		
		$product = $this->helper->getProduct($request->get('productId'), $context);
		
		if (empty($product))
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Product Information is not available']);
		}
		
		$price = $product->getPrice()->first();
		
		// Fetch the parent subscription information for variant products
		if(!empty($product->getParentId())){
			$criteria = new Criteria();
			$criteria->addFilter(new EqualsFilter('productId', $product->getParentId()));
			$parentSubscriptionDetails =  $this->novalnetProductRepository->search($criteria, $context)->first();
		}
		
		$data ['id'] = Uuid::randomHex();
		$data ['productId'] = strtolower($request->get('productId'));
		
		// define datatype for some fields
		$dataType = ['boolean' => ['active', 'multipleSubscription'], 'integer' => ['interval', 'subscriptionLength', 'freeTrial', 'operationalMonth']];
		
		$defaults = ['type' => 'opt_abo', 'interval' => 1, 'period' => 'days', 'subscriptionLength' => 0, 'freeTrial' => 0, 'multipleSubscription' => false, 'discount' => 0, 'discountScope' => 'all', 'discountType' => 'percentage', 'predefinedSelection' => 'subscription'];
		
		foreach (['active', 'type', 'interval', 'period', 'subscriptionLength', 'signUpFee', 'freeTrial', 'freeTrialPeriod', 'multipleSubscription', 'multiSubscriptionOptions', 'operationalMonth', 'discount'
		, 'discountScope', 'discountType', 'detailPageText', 'predefinedSelection'] as $key)
		{
			if (!is_null($request->get($key)))
			{
				$data [$key] = $request->get($key);
				
				foreach ($dataType as $dataTypeKey => $dataTypeValue)
				{	
					if (in_array($key, $dataTypeValue) && gettype($request->get($key)) != $dataTypeKey)
					{
						return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `'. $key . '` excepted data type - '.  $dataTypeKey]);
					}
				}
				
				if (($key == 'discount' || $key == 'signUpFee') && !in_array(gettype($request->get($key)), ['double', 'integer']))
				{
					return new JsonResponse(['success' => false, 'errorMessage' => 'Invalid data type for the field `'. $key . '` excepted data type - double (or) integer']);
				}
				
			} elseif (!empty($parentSubscriptionDetails) && !is_null($parentSubscriptionDetails->get($key)))
			{
				$data [$key] = $parentSubscriptionDetails->get($key);
			} elseif (!is_null($defaults[$key])) 
			{
				$data [$key] = $defaults[$key];
			}
		}
		
		$data ['subscriptionLength'] = $data ['interval'] * $data ['subscriptionLength'];
		
		if (!empty($data['discountType']) && $data['discountType'] == 'percentage' && $data['discount'] > 100)
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Discount should be lesser than or equal to 100 percentage']);
		} elseif (!empty($data['discountType']) && $data['discountType'] == 'fixed' && $data['discount'] > $price->getGross())
		{
			return new JsonResponse(['success' => false, 'errorMessage' => 'Discount should be lesser than or equal to product price amount']);
		}
		
		$this->novalnetProductRepository->upsert([$data], $context);
		
		return new JsonResponse(['success' => true]);
	}
	
	public function discountDetails (Request $request, Context $context) :JsonResponse 
	{
		$product = $request->get('product');
		if(!empty($product)){
			
			$localeCode = $this->helper->getLocaleFromOrder($context->getLanguageId());
			$novalnetConfiguration = $product['extensions']['novalnetConfiguration'];
			
			if(isset($novalnetConfiguration['_isNew']) && !empty($novalnetConfiguration['_isNew']) && !empty($product['parentId'])){
				
				$criteria = new Criteria();
			    $criteria->addFilter(new EqualsFilter('productId', $product['parentId']));
			    $parentSubscriptionDetails =  $this->novalnetProductRepository->search($criteria, $context)->first();
				unset($novalnetConfiguration['extensions']);
				unset($novalnetConfiguration['_isNew']);
				foreach ($novalnetConfiguration as $key =>$value){
				      $novalnetConfiguration [$key] = $parentSubscriptionDetails->get($key);
			    }
			   $novalnetConfiguration['active'] =  1;
			}

			if(!empty($novalnetConfiguration['multipleSubscription'])){	
				$cycle= [
						$request->get('cycle') => (object)[ 'period' => $request->get('cycle'),
						'type' => $request->get('discountType'),
						'discount' => $request->get('discountValue')],
						];
				
				if(isset($novalnetConfiguration['discountDetails']) && $novalnetConfiguration['discountDetails'] !=null){
					
					$discount = (array) json_decode($novalnetConfiguration['discountDetails']);
						if(!empty($discount)){
							
							foreach((array)$discount as $key=>$value){
								if($key == $request->get('cycle')){
									return new JsonResponse(['success' => false, 'errorMessage' => $this->translator->trans('NovalnetSubscription.text.existsPeriod', [], null, $localeCode)]);
								}
							} 
							
							$discount= (object) array_merge((array) $discount, (array) $cycle );
						} 
						$discountDetails= (object) $discount;
						
				} else {
						$discountItem =  (object) $cycle;
						$discountDetails = (object) $discountItem;
				}

				$novalnetConfiguration['discountDetails'] = json_encode($discountDetails, JSON_UNESCAPED_SLASHES);
				
				return $this->NovalnetProduct($request, $novalnetConfiguration, $context);
			}
			return new JsonResponse(['success' => false, 'errorMessage' => 'Multiple Subscription not valid']);	
		}
	    return new JsonResponse(['success' => false, 'errorMessage' => 'product is empty']);
	}
	
	public function NovalnetProduct(Request $request, array $novalnetConfiguration, Context $context) :JsonResponse 
    {
		$product = $request->get('product');
			if(!empty($novalnetConfiguration)){
				
				$this->novalnetProductRepository->upsert(
					[
						[
							'id'       => !empty($novalnetConfiguration['id']) ? $novalnetConfiguration['id'] : Uuid::randomHex(),
							'active' => (bool) (($novalnetConfiguration['active']) ? $novalnetConfiguration['active'] : 1 ),
							'type' => $novalnetConfiguration['type'],
							'interval' => (int) $novalnetConfiguration['interval'],
							'period' => $novalnetConfiguration['period'],
							'subscriptionLength' => (int) $novalnetConfiguration['subscriptionLength'],
							'signUpFee' => isset($novalnetConfiguration['signUpFee']) ? $novalnetConfiguration['signUpFee'] : null,
							'freeTrial' => (int) $novalnetConfiguration['freeTrial'],
							'freeTrialPeriod' => $novalnetConfiguration['freeTrialPeriod'],
							'multipleSubscription' => (bool) $novalnetConfiguration['multipleSubscription'],
							'multiSubscriptionOptions' => $novalnetConfiguration['multiSubscriptionOptions'],
							'operationalMonth' => (int) $novalnetConfiguration['operationalMonth'],
							'discount' =>  $novalnetConfiguration['discount'],
							'discountScope' =>  $novalnetConfiguration['discountScope'],
							'discountType' =>  $novalnetConfiguration['discountType'],
							'detailPageText' =>  isset($novalnetConfiguration['detailPageText']) ? $novalnetConfiguration['detailPageText'] : null,
							'predefinedSelection' =>  $novalnetConfiguration['predefinedSelection'],
							'discountDetails' =>  $novalnetConfiguration['discountDetails'],
							'productId' => $product['id'],
						]
					],
					$context
				);
				return new JsonResponse(['success' => true]);
			}
		
	}
	
	public function periodUpdated(Request $request, Context $context) :JsonResponse 
	{
		if(!empty($request->get('productId'))){
			
			$criteria = new Criteria();
			$criteria->addFilter(new EqualsFilter('productId', $request->get('productId')));
			$novalnetConfiguration =  $this->novalnetProductRepository->search($criteria, $context)->first();
			
			$localeCode = $this->helper->getLocaleFromOrder($context->getLanguageId());

			$discount = (array) json_decode($novalnetConfiguration->getDiscountDetails());
			unset($discount[$request->get('peroidTerm')]); 
			$cycle  = [
				$request->get('peroidTerm') => (object)[ 'period' => $request->get('peroidTerm'),
				'type' => $request->get('discountType'),
				'discount' => $request->get('discountValue')],
			];
			
			$discountDetails = (object) array_merge((array) $discount, (array) $cycle );
			$discountItem = json_encode( $discountDetails, JSON_UNESCAPED_SLASHES);
			$this->novalnetProductRepository->upsert( [
				[
					'discountDetails' =>  $discountItem,
					'id' => $novalnetConfiguration->getId()
				]
			],$context );

			return new JsonResponse(['success' => true]);
		}
		return new JsonResponse(['success' => false, 'errorMessage' => 
		 'Request is empty']);
	}
	
	public function periodDelete(Request $request, Context $context) :JsonResponse 
	{	
		if(!empty($request->get('productId')) && !empty($request->get('item'))){
			
			$criteria = new Criteria();
			$criteria->addFilter(new EqualsFilter('productId', $request->get('productId')));
			$novalnetConfiguration =  $this->novalnetProductRepository->search($criteria, $context)->first();
			
			$product = $request->get('product');
			$item = $request->get('item');
			$periodterm = $item['periodterm'];
			$localeCode = $this->helper->getLocaleFromOrder($context->getLanguageId());
			$discountDetails = (array) (json_decode($novalnetConfiguration->getDiscountDetails()));
			
			unset($discountDetails[$periodterm]);
			
			if(!empty($discountDetails)){
			  $discountItem = json_encode( (object) $discountDetails, JSON_UNESCAPED_SLASHES);
		    }
		    else {
				$discountItem = null;
			}

			$this->novalnetProductRepository->upsert( [
						[
							'discountDetails' =>  $discountItem,
							'id' => $novalnetConfiguration->getId(),
						]
					],$context );

			return new JsonResponse(['success' => true]);
		}
		return new JsonResponse(['success' => false, 'errorMessage' => 
		'Request is empty']);
	}
	
	public function cycleDurationDiscount(NovalnetSubscriptionEntity $abo, NovalnetProductConfigurationEntity $novalnetConfiguration) : array
	{
		$data ['discountScope'] = $novalnetConfiguration->getDiscountScope();
		if($novalnetConfiguration->getDiscountDetails() !=null && !empty($novalnetConfiguration->getDiscountDetails())){
			$discountDetails = (array) (json_decode($novalnetConfiguration->getDiscountDetails()));
			$intervalUnit = $this->helper->getPeriod($abo->getInterval(), $abo->getUnit());
			$discountData =[];
			if(!empty($intervalUnit)){
				foreach($discountDetails as $discountPeriod=>$discountValue){
					if($discountPeriod == $intervalUnit){
							$discountData = (array) $discountValue;
					}
				} 
			}
			$data ['discount']      = !empty($discountData) ? $discountData['discount'] : $novalnetConfiguration->getDiscount();
			$data ['discountType']  = !empty($discountData) ? $discountData['type'] : $novalnetConfiguration->getDiscountType();
			
		} else {
			$data ['discount']      = 0;
			$data ['discountType']  = 'percentage';
		}
		
		return $data;
	}
	
	
    
  
}
