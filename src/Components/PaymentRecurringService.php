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
namespace Novalnet\NovalnetSubscription\Components;

use DateTime;
use Psr\Log\LoggerInterface;
use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Novalnet\NovalnetSubscription\Components\NovalnetSubsCycleRepository;
use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubscriptionEntity;
use Novalnet\NovalnetSubscription\Helper\Helper;
use Shopware\Core\Checkout\Cart\Exception\InvalidCartException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerRegistry;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\SynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\SyncPaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentRecurringService
{
    /**
     * @var EntityRepository
     */
    private $subscriptionRepository;
    
    /**
     * @var NovalnetSubsCycleRepository
     */
    public $subsCycleRepository;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var Helper
     */
    private $helper;
    
    /**
     * @var Context
     */
    private $context;
    
    /**
     * @var PaymentHandlerRegistry
     */
    private $paymentHandlerRegistry;
    
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        LoggerInterface $logger,
        EntityRepository $subscriptionRepository,
        NovalnetSubsCycleRepository $subsCycleRepository,
        Helper $helper,
        RouterInterface $router,
        PaymentHandlerRegistry $paymentHandlerRegistry
    ) {
        $this->logger       = $logger;
        $this->helper       = $helper;
        $this->subsCycleRepository  = $subsCycleRepository;
        $this->subscriptionRepository   = $subscriptionRepository;
        $this->context      = Context::createDefaultContext();
        $this->router       = $router;
        $this->paymentHandlerRegistry   = $paymentHandlerRegistry;
    }
    
    public function run()
    {
        $this->logger->info('Starting recurring payments process');
        $checkDate = new \DateTime();
        $checkDate->setTimezone(new \DateTimeZone('UTC'));
        
        $criteria = new Criteria();
        $criteria->addAssociation('subsOrders');
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.transactions');
        $criteria->addAssociation('order.salesChannel');
        $criteria->addAssociation('order.salesChannel.paymentMethods');
        $criteria->addAssociation('payment_method');
        $criteria->addFilter(new EqualsFilter('status', NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE));
        $criteria->addFilter(new NotFilter('AND', [new EqualsFilter('nextDate', null)]));
        $criteria->addFilter(new RangeFilter('nextDate', ['lte' => $checkDate->format('Y-m-d H:i:s')]));
        $subscriptionOrders = $this->subscriptionRepository->search($criteria, $this->context);

        if (!empty($subscriptionOrders) && $subscriptionOrders->count() > 0) {
            foreach ($subscriptionOrders as $subscription) {
                if (!$subscription instanceof NovalnetSubscriptionEntity) {
                    continue;
                }
                
                $salesChannelPaymentMethods = [];
                $subsOrders     = $subscription->get('subsOrders');
                $paymentMethod  = $subscription->get('payment_method');
                
                if (!empty($paymentMethod) && empty($paymentMethod->getActive())) {
                    $paymentHandler = $this->helper->getNovalnetPaymentHandler($paymentMethod->getHandlerIdentifier());
                    
                    if (!empty($paymentHandler) && in_array($paymentHandler, ['NovalnetInvoiceGuarantee', 'NovalnetSepa', 'NovalnetApplePay', 'NovalnetPrepayment', 'NovalnetSepaGuarantee', 'NovalnetInvoice', 'NovalnetGooglePay', 'NovalnetCreditCard'])) {
                        $novalnetPaymentmethod = $this->helper->getPaymentMethod();
                        if (!empty($novalnetPaymentmethod)) {
                            $paymentMethod = $novalnetPaymentmethod;
                            $subscription->setPaymentMethodId($paymentMethod->getId());
                        }
                    }
                }
                $orderCustomer      = $subscription->get('order')->getOrderCustomer();
                $salesChannelEntity = $subscription->get('order')->getSalesChannel();
                $orderTransaction   = $this->helper->getLastOrderTransaction($subscription->get('order')->getId(), $orderCustomer->getCustomerId(), $this->context);
                $salesChannelContext = $this->helper->getSalesChannelContext($subscription, $salesChannelEntity, $orderTransaction);
                foreach ($salesChannelEntity->getPaymentMethods() as $key => $payment) {
                    $salesChannelPaymentMethods[] = $key;
                }
                
                if (empty($orderTransaction)) {
                    $this->logger->warning('no order transaction could be found for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId()]);
                    continue;
                }
                
                $lastAboCycleData = null;
                foreach ($subsOrders as $abo) {
                    if (is_null($abo->getOrderId()) || $abo->getStatus() == NovalnetSubscription::CYCLE_STATUS_RETRY || $abo->getStatus() == NovalnetSubscription::CYCLE_STATUS_FAILURE) {
                        $lastAboCycleData = $abo;
                    }
                }
                
                $count = empty($subscription->getTrialInterval()) ? (int) $subsOrders->count() : (int) ($subsOrders->count() - 1);
                if (empty($lastAboCycleData) && !empty($subscription->getLength()) && ($count >= $subscription->getLength())) {
                    $this->subscriptionRepository->upsert([[
                        'id' => $subscription->getId(),
                        'status' => NovalnetSubscription::SUBSCRIPTION_STATUS_EXPIRED,
                        ]], $salesChannelContext->getContext());
                    $this->logger->warning('All recurring order is completed for the subscription {subscriptionId}, So subscription is expired', ['subscriptionId' => $subscription->getId()]);
                    continue;
                } else {
                    if (!empty($paymentMethod) && $paymentMethod->getActive() == 1 && in_array($paymentMethod->getId(), $salesChannelPaymentMethods)) {
                        try {
                            $this->logger->debug('start creating new order for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId()]);
                            $createdOrder = $this->helper->createNewOrder($subscription, $orderTransaction, $salesChannelContext, $paymentMethod);
                            if (empty($createdOrder)) {
                                $this->logger->warning('Recurring order not completed for the subscription {subscriptionId}', ['subscriptionId' => $subscription->getId()]);
                                continue;
                            }
                            $createdOrderTransactions = $createdOrder->getTransactions();
                            assert($createdOrderTransactions instanceof OrderTransactionCollection);
                            $latestTransaction = $createdOrderTransactions->last();
                            if (!$latestTransaction instanceof OrderTransactionEntity) {
								$this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, 'something went wrong here. there is no order transaction is found.', $createdOrder->getId());
                                $this->logger->emergency(
                                    'something went wrong here. there is no order transaction is found.'
                                );
                                continue;
                            }
                            
                            if (!empty($lastAboCycleData)) {
                                $periods    = ['d' => 'days', 'w' => 'weeks', 'm' => 'months', 'y' => 'years'];
                                $status = NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING_CANCEL;
                                
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
											continue;
                                        }
                                    } catch (\Exception $e) {
										$this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, $e->getMessage());
										$this->logger->emergency('could not create a order for subscription {subscriptionId}', ['subscriptionId' => $subscription->getId(), 'cartErrors' => $e->getMessage()]);
										continue;
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
                                $this->subsCycleRepository->updateCycles($salesChannelContext, $data);
                                
                                if ($subscription->getLength() == 0 || $subscription->getLength() > $count && $cycleStatus == NovalnetSubscription::CYCLE_STATUS_SUCCESS) {
                                    $data['orderId']    = null;
                                    $data['cycles']     = $count + 1;
                                    $data['status']     = NovalnetSubscription::CYCLE_STATUS_PENDING;
                                    $data['cycleDate']  = $this->helper->getFormattedDate($subscription->getInterval(), $periods[$subscription->getUnit()], date('Y-m-d H:i:s'));
                                
                                    // Save Next Subscription cycle data
                                    $this->subsCycleRepository->insertSubscriptionCycles($salesChannelContext, $data);
                                    $status = NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE;
                                }
                                
								// Update next cycle date in subscription tabel
								$this->subscriptionRepository->upsert([[
									'id'    => $subscription->getId(),
									'status'    => $status,
									'nextDate'  => $this->helper->getUpdatedNextDate($subscription->getInterval(), $periods[$subscription->getUnit()], $subscription->getNextDate()->format('Y-m-d H:i:s'), $subscription, $salesChannelContext->getContext())
								]], $salesChannelContext->getContext());
                                
                                
                                if ($status == NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING_CANCEL) {
                                    $this->helper->sendRenewalReminder($subscription, $salesChannelContext->getContext());
                                }
                            } else {
                                $this->logger->debug('subscription cycle data is already updated.');
                                $this->logger->debug('there is no entries to update the cycles.');
                            }
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
                            
                            $this->logger->emergency(
                                'could not create a order for subscription {subscriptionId}',
                                ['subscriptionId' => $subscription->getId(), 'cartErrors' => $errorMessage]
                            );
                            continue;
                        }
                    } else {
						$this->helper->updateRetrySubscription($subscription, $lastAboCycleData, $salesChannelContext, 'Payment method is not available/not active for this recurring');
						
                        $this->logger->notice('Payment method is not available/not active for this recurring {subscriptionId}.', ['subscriptionId' => $subscription->getId()]);
                        continue;
                    }
                }
            }
        } else {
            $this->logger->notice('No subscription orders are found, So recurring is stopped.');
        }
             
        $this->logger->info('Finished recurring payments process');
        
        // Set the pendign cancel subscription into expired status
        $this->setExpiredForOrders();
    }

    public function setExpiredForOrders()
    {
        $checkDate = new \DateTime();
        $checkDate->setTimezone(new \DateTimeZone('UTC'));
        
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('status', NovalnetSubscription::SUBSCRIPTION_STATUS_PENDING_CANCEL));
        $criteria->addFilter(new NotFilter('AND', [new EqualsFilter('nextDate', null)]));
        $criteria->addFilter(new RangeFilter('nextDate', ['lte' => $checkDate->format('Y-m-d H:i:s')]));
        $subscriptions = $this->subscriptionRepository->search($criteria, $this->context);
        
        $this->logger->info('Checking for pending cancel orders');
        if ($subscriptions->count() > 0) {
            foreach ($subscriptions as $subscription) {
                if (!$subscription instanceof NovalnetSubscriptionEntity) {
                    continue;
                }
                
                $this->logger->info('Changing the subscription status for the Id.' . $subscription->getId());
                
                // Update next cycle date in subscription tabel
                $this->subscriptionRepository->upsert([[
                    'id'    => $subscription->getId(),
                    'status'    => NovalnetSubscription::SUBSCRIPTION_STATUS_EXPIRED
                ]], $this->context);
            }
        } else {
            $this->logger->notice('There is no pending cancellation orders available.');
        }
    }
}
