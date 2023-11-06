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

namespace Novalnet\NovalnetSubscription\Helper;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Psr\Log\LoggerInterface;
use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubscriptionEntity;
use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubsCycleEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Content\MailTemplate\Service\MailService as ArchiveMailService;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\Product\Cart\ProductCartProcessor;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryProcessor;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryDate;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryPositionCollection;
use Shopware\Core\Checkout\Cart\Delivery\Struct\DeliveryInformation;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\SalesChannel\AbstractCartOrderRoute;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressCollection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\CustomerNotFoundException;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\DBAL\Connection;

/**
 * Helper Class.
 */
class Helper
{
    /**
     * @var object
     */
    public $contextFactory;
    
    /**
     * @var CartService
     */
    public $cartService;
    
    /**
     * @var QuantityPriceCalculator
     */
    public $quantityPriceCalculator;
    
    /**
     * @var AbstractCartOrderRoute
     */
    public $cartOrderRoute;
    
    /**
     * @var EntityRepository
     */
    public $orderRepository;
    
    /**
     * @var EntityRepository
     */
    public $customerAddressRepository;
    
    /**
     * @var ContainerInterface
     */
    public $container;
    
    /**
     * @var MailService|null
     */
    public $archiveMailService;
    
    /**
     * @var AbstractMailService|null
     */
    public $mailService;
    
    /**
     * @var RequestStack
     */
    protected $requestStack;
    
    /**
     * @var SystemConfigService
     */
    public $systemConfigService;
    
    /**
     * @var LoggerInterface
     */
    public $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        object $contextFactory,
        CartService $cartService,
        QuantityPriceCalculator $quantityPriceCalculator,
        AbstractCartOrderRoute $cartOrderRoute,
        ContainerInterface $container,
        ArchiveMailService $archiveMailService = null,
        AbstractMailService $mailService = null,
        RequestStack $requestStack,
        LoggerInterface $logger
    ) {
        $this->systemConfigService       = $systemConfigService;
        $this->quantityPriceCalculator   = $quantityPriceCalculator;
        $this->cartOrderRoute            = $cartOrderRoute;
        $this->contextFactory            = $contextFactory;
        $this->cartService               = $cartService;
        $this->container                 = $container;
        $this->orderRepository           = $container->get('order.repository');
        $this->customerAddressRepository = $container->get('customer_address.repository');
        $this->mailService               = $archiveMailService ?? $mailService;
        $this->requestStack              = $requestStack;
        $this->logger                    = $logger;
    }
      
    /**
     * get the order line item.
     *
     * @param string $orderId
     * @param SalesChannelContext $salesChannelContext
     *
     * @return OrderEntity|null
     */
    public function getOrderLineItem(string $orderId, SalesChannelContext $salesChannelContext): ?OrderEntity
    {
        $orderCriteria = new Criteria();
        $orderCriteria->addFilter(new EqualsFilter('id', $orderId));
        $orderCriteria->addAssociation('order');
        $orderCriteria->addAssociation('lineItems');
        return $this->orderRepository->search($orderCriteria, $salesChannelContext->getContext())->first();
    }
    
    /**
     * Send Cancellation Mail.
     *
     * @param string $aboId
     * @param Context $context
     * @param string $mailType
     * @param string $previousData
     *
     * @return void
     */
    public function sendMail(string $aboId, Context $context, string $mailType, string $previousData = ''): void
    {
        // fetch the subscription and order details
        $criteria = new Criteria([$aboId]);
        $criteria->addAssociation('product');
        $criteria->addAssociation('product.options');
        $criteria->addAssociation('product.options.group');
        $criteria->addAssociation('payment_method');
        $criteria->addAssociation('order');
        $criteria->addAssociation('order.currency');
        $criteria->addAssociation('order.orderCustomer');
        $criteria->addAssociation('order.orderCustomer.salutation');
        $criteria->addAssociation('order.orderCustomer.customer');
        $criteria->addAssociation('order.orderCustomer.customer.addresses');
        $criteria->addAssociation('order.stateMachineState');
        $criteria->addAssociation('order.lineItems');
        $criteria->addAssociation('order.transactions');
        $criteria->addAssociation('order.transactions.paymentMethod');
        $criteria->addAssociation('order.addresses');
        $criteria->addAssociation('order.deliveries.shippingMethod');
        $criteria->addAssociation('order.addresses.country');
        $criteria->addAssociation('order.deliveries.shippingOrderAddress.country');
        $criteria->addAssociation('order.price');
        $criteria->addAssociation('order.taxStatus');
        $criteria->addAssociation('order.salesChannel');
        $criteria->addAssociation('order.salesChannel.domains');
        
        $criteria->setLimit(1);
        
        $abo = $this->container->get('novalnet_subscription.repository')->search($criteria, $context)->first();
        
        if (!empty($abo)) {
            $order  = $abo->order;
            $salesChannel  = $order->getSalesChannel();
            $salesChannelContext = $this->getSalesChannelContext($abo, $salesChannel, $order);
            $mailTemplate = $this->getMailTemplate($salesChannelContext->getContext(), $mailType);
            $customer = $order->getOrderCustomer();

            if (empty($customer) || empty($mailTemplate)) {
                return;
            }
            
            $data = new ParameterBag();
            
            $emailRecipients = [$customer->getEmail() => $customer->getFirstName().' '.$customer->getLastName()];
            
            if ($mailType == 'novalnet_cancellation_mail') {
                $systemConfig = $this->getSubscriptionSettings($salesChannel->getId());
                $toEmail = !empty($systemConfig['NovalnetSubscription.config.notifyEmail']) ? explode(',', $systemConfig['NovalnetSubscription.config.notifyEmail']) : [];
                if ($toEmail) {
                    foreach ($toEmail as $email) {
                        if ($this->isValidEmail($email)) {
                            $data->set('recipientsCc', $email);
                        }
                    }
                }
            }
            
            $abo->setLength($abo->getLength() * $abo->getInterval());
            
            $mailTemplateData = ['order' => $order, 'salesChannel' => $order->getSalesChannel(), 'subs' => $abo];
            
            if ($abo->get('product') && $mailType == 'novalnet_product_change_mail')
			{
				if (empty($abo->get('product')->getTranslated()['name']) && empty($abo->get('product')->getName()))
				{
					$product = $this->getParentProductId($abo->get('product')->getParentId(), $context);
					$productName = !empty($product->getTranslated()['name']) ? $product->getTranslated()['name'] : $product->getName();
				} else {
					$productName = !empty($abo->get('product')->getTranslated()['name']) ? $abo->get('product')->getTranslated()['name'] : $abo->get('product')->getName();
				}
				
				if (!empty($abo->get('product')->getOptions()->getElements()))
				{
					$productName .= ' ( ';
					foreach ($abo->get('product')->getOptions()->getElements() as $element) {
						$productName .= (!empty($element->getGroup()->getTranslated()['name']) ? $element->getGroup()->getTranslated()['name'] : $element->getGroup()->getName()) . ': ';
						$productName .= (!empty($element->getTranslated()['name']) ? $element->getTranslated()['name'] : $element->getGroup()->getName()) . ' | ';
					}
					
					$productName = substr($productName, 0, -3);
					$productName .= ' )';
				}
				
				$mailTemplateData['previousProductName'] = $previousData;
				$mailTemplateData['currentProductName']  = $productName;
			} elseif ($abo->get('payment_method') && $mailType == 'novalnet_payment_change_mail')
			{
				$mailTemplateData['currentPaymentName']  = $previousData;
			}
            
            $data->set(
                'recipients',
                $emailRecipients
            );
            $data->set('senderName', $mailTemplate->getSenderName());
            $data->set('salesChannelId', $order->getSalesChannelId());

            $data->set('contentHtml', $mailTemplate->getContentHtml());
            $data->set('contentPlain', $mailTemplate->getContentPlain());
            $data->set('subject', $mailTemplate->getSubject());
            
            try {
                $this->mailService->send(
                    $data->all(),
                    $context,
                    $mailTemplateData
                );
            } catch (\Exception $e) {
                $this->logger->error(
                    "Could not send mail:\n"
                    . $e->getMessage() . "\n"
                    . 'Error Code:' . $e->getCode() . "\n"
                    . "Template data: \n"
                    . json_encode($data->all()) . "\n"
                );
            }
        }
    }
    
    /**
     * Send Cancellation Mail.
     *
     * @param SalesChannelContext $salesChannelContext
     * @param string $message
     * @param string $subject
     *
     * @return void
     */
    public function sendNotificationMail(SalesChannelContext $salesChannelContext, string $message, string $subject): void
    {
        $email = $this->systemConfigService->get('core.basicInformation.email');
        
        // return if the mail ID is not found
        if (!$email) {
            return;
        }
        
        $data = new ParameterBag();
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();
        
        $data->set('recipients', [$email => $email]);
        $data->set('senderName', $salesChannelContext->getSalesChannel()->getName());
        $data->set('salesChannelId', $salesChannelId);

        $data->set('contentHtml', $message);
        $data->set('contentPlain', $message);
        $data->set('subject', $subject);
            
        try {
            $this->mailService->send(
                $data->all(),
                $salesChannelContext->getContext(),
                []
            );
        } catch (\Exception $e) {
            $this->logger->error(
                "Could not send mail:\n"
                . $e->getMessage() . "\n"
                . 'Error Code:' . $e->getCode() . "\n"
                . "Template data: \n"
                . json_encode($data->all()) . "\n"
            );
        }
    }
    
    /**
     * get the order mail template.
     *
     * @param Context $context
     * @param string $technicalName
     *
     * @return MailTemplateEntity
     */
    public function getMailTemplate(Context $context, string $technicalName): ?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->setLimit(1);

        /** @var MailTemplateEntity|null $mailTemplate */
        return $this->container->get('mail_template.repository')->search($criteria, $context)->first();
    }
    
    /**
     * Unset Novalnet session
     *
     * @param string $sessionKey
     *
     */
    public function unsetSession(string $sessionKey): void
    {
        if ($this->hasSession($sessionKey)) {
            $this->removeSession($sessionKey);
        }
    }
    
    /**
     * Returns the Novalnet Subscription backend configuration.
     *
     * @param string|null $salesChannelId
     *
     * @return array
     */
    public function getSubscriptionSettings(string $salesChannelId = null): array
    {
        return $this->systemConfigService->getDomain(
            'NovalnetSubscription.config',
            $salesChannelId,
            true
        );
    }
    
    /**
     * Check mail if validate or not.
     *
     * @param string $mail
     *
     * @return bool
     */
    public function isValidEmail($mail): bool
    {
        return (bool) (new EmailValidator())->isValid($mail, new RFCValidation());
    }
    
    /**
     * Returns the formatted period.
     *
     * @param string $period
     *
     * @return string
     */
    public function getFormattedPeriod(string $period): string
    {
        return substr($period, 0, 1);
    }
    
    /**
     * Returns the formatted date.
     *
     * @param int $interval
     * @param string $period
     * @param string $date
     *
     * @return string
     */
    public function getFormattedDate(int $interval, string $period, $date): string
    {
		return date('Y-m-d H:i:s', strtotime('+ '. $interval . $period, strtotime($date)));
    }
    
    /**
     * Returns the formatted date.
     *
     * @param int $interval
     * @param string $period
     * @param string $date
     * @param NovalnetSubscriptionEntity|null $subscription
     * @param Context $context
     *
     * @return string
     */
    public function getUpdatedNextDate(int $interval, string $period, $date, NovalnetSubscriptionEntity $subscription = null, Context $context): string
    {
		if (!empty($subscription) && !empty($subscription->getLastDayMonth()) && $period == 'months')
		{
			return date('Y-m-d H:i:s', strtotime("last day of +$interval month", strtotime($date)));
		} elseif ($period == 'months')
		{
			$currentRecurringDay = date("d", strtotime($date));
			$nextRecurringDay    = date("d", strtotime($date."+$interval month"));
			
			if ($currentRecurringDay == $nextRecurringDay) {
				$nextDate = date('Y-m-d H:i:s', strtotime($date." +$interval month"));
			} else {
				if (!empty($subscription))
				{
					$this->container->get('novalnet_subscription.repository')->upsert([[
						'id'    => $subscription->getId(),
						'lastDayMonth'  => true
					]], $context);
				}
				$nextDate = date('Y-m-d H:i:s', strtotime("last day of +$interval month", strtotime($date)));
			}
			
			return $nextDate;
		}
		
		return date('Y-m-d H:i:s', strtotime('+ '. $interval . $period, strtotime($date)));
    }
    
    /**
     * Returns the line item ID.
     *
     * @param object $orderLineItem
     *
     * @return string
     */
    public function getLineItemId(object $orderLineItem): string
    {
        $lineItemSettings = str_replace($orderLineItem->getReferencedId(), '', $orderLineItem->getIdentifier());
        
        return $orderLineItem->getReferencedId(). '_' . substr($lineItemSettings, 0, 1). '_' .substr($lineItemSettings, 1);
    }
    
    /**
     * Returns the Order and transaction of the last executed order.
     *
     * @param string $orderId
     * @param string $customerId
     *
     * @return object
     */
    public function getLastOrderTransaction($orderId, $customerId, Context $context)
    {
        $orderCriteria = new Criteria();
        if ($orderId) {
            $orderCriteria = new Criteria([$orderId]);
            $orderCriteria->addFilter(
                new EqualsFilter('order.id', $orderId)
            );
        }

        $orderCriteria->addAssociation('orderCustomer.salutation');
        $orderCriteria->addAssociation('orderCustomer.customer');
        $orderCriteria->addAssociation('orderCustomer.customer.addresses');
        $orderCriteria->addAssociation('currency');
        $orderCriteria->addAssociation('stateMachineState');
        $orderCriteria->addAssociation('lineItems');
        $orderCriteria->addAssociation('transactions');
        $orderCriteria->addAssociation('transactions.paymentMethod');
        $orderCriteria->addAssociation('addresses');
        $orderCriteria->addAssociation('deliveries.shippingMethod');
        $orderCriteria->addAssociation('addresses.country');
        $orderCriteria->addAssociation('deliveries.shippingOrderAddress.country');
        $orderCriteria->addAssociation('salesChannel');
        $orderCriteria->addAssociation('price');
        $orderCriteria->addAssociation('billingAddress');
        
        return $this->orderRepository->search($orderCriteria, $context)->first();
    }
    
    /**
     * Returns the saleschannel context for Api source.
     *
     * @param NovalnetSubscriptionEntity $subscription
     * @param SalesChannelEntity $entity
     * @param OrderEntity $order
     *
     * @return SalesChannelContext
     */
    public function getSalesChannelContextForApi(NovalnetSubscriptionEntity $subscription, SalesChannelEntity $entity, OrderEntity $order): SalesChannelContext
    {
        $orderCustomer = $order->getOrderCustomer();
        assert($orderCustomer instanceof OrderCustomerEntity);
        $customer = $orderCustomer->getCustomer();
        assert($customer instanceof CustomerEntity);
        $deliveries = $order->getDeliveries();
        assert($deliveries instanceof OrderDeliveryCollection);
        $delivery = $deliveries->first();
        
        $languageId = $order->getLanguageId() ?? $entity->getLanguageId();
        
        $options = [
            SalesChannelContextService::LANGUAGE_ID => $languageId,
            SalesChannelContextService::CUSTOMER_ID => $orderCustomer->getCustomerId(),
            SalesChannelContextService::SHIPPING_METHOD_ID => $delivery->getShippingMethodId(),
            SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId() ?? $order->getCurrency()->getId(),
            SalesChannelContextService::PAYMENT_METHOD_ID => $subscription->getPaymentMethodId()
        ];

        return $this->contextFactory->create(Uuid::randomHex(), $entity->getId(), $options);
    }
    
    /**
     * Returns the saleschannel context.
     *
     * @param NovalnetSubscriptionEntity $subscription
     * @param SalesChannelEntity $entity
     * @param OrderEntity $order
     *
     * @return SalesChannelContext
     */
    public function getSalesChannelContext(NovalnetSubscriptionEntity $subscription, SalesChannelEntity $entity, OrderEntity $order): SalesChannelContext
    {
        $orderCustomer = $order->getOrderCustomer();
        assert($orderCustomer instanceof OrderCustomerEntity);
        $customer = $orderCustomer->getCustomer();
        assert($customer instanceof CustomerEntity);
        $deliveries = $order->getDeliveries();
        assert($deliveries instanceof OrderDeliveryCollection);
        $delivery = $deliveries->first();
        
        if (!$customer instanceof CustomerEntity) {
            throw new CustomerNotFoundException($orderCustomer->getEmail());
        }
        
        $billingAddress   = $order->getBillingAddress();
		$billingAddressId = $shippingAddressId = null;
        if (!empty($billingAddress)) {
            $billingAddressId = $this->getBillingAddressId($billingAddress);

            if (!empty($delivery) && $delivery->getShippingOrderAddress() != null) {
                $shippingAddress = $delivery->getShippingOrderAddress();
            } else {
                $shippingAddress =  $billingAddress;
            }
            
            $shippingAddressId = $this->getBillingAddressId($shippingAddress);
        }
        $languageId = $order->getLanguageId() ?? $entity->getLanguageId();
        
        $options = [
           SalesChannelContextService::LANGUAGE_ID => $languageId,
           SalesChannelContextService::CUSTOMER_ID => $customer->getId(),
           SalesChannelContextService::CURRENCY_ID => $order->getCurrencyId() ?? $order->getCurrency()->getId(),
           SalesChannelContextService::PAYMENT_METHOD_ID => $subscription->getPaymentMethodId()
        ];
        
        if ($shippingAddressId)
        {
			$options[SalesChannelContextService::SHIPPING_ADDRESS_ID] = $shippingAddressId;
		}
		
		if ($billingAddressId)
        {
			$options[SalesChannelContextService::BILLING_ADDRESS_ID] = $billingAddressId;
		}
		
		if (!empty($delivery))
		{
			$options[SalesChannelContextService::SHIPPING_METHOD_ID] = $delivery->getShippingMethodId();
		}
		
        return $this->contextFactory->create(Uuid::randomHex(), $entity->getId(), $options);
    }
    
    /**
     * Create subscription recurring orders.
     *
     * @param NovalnetSubscriptionEntity $abo
     * @param OrderEntity $order
     * @param SalesChannelContext $salesChannelContext
     * @param PaymentMethodEntity $paymentMethod
     *
     * @return OrderEntity|null
     */
    public function createNewOrder(NovalnetSubscriptionEntity $abo, OrderEntity $order, SalesChannelContext $salesChannelContext, PaymentMethodEntity $paymentMethod): ?OrderEntity
    {
        $orderTransaction = $order->getTransactions()->last();
        assert($orderTransaction instanceof OrderTransactionEntity);
        $orderCustomer = $order->getOrderCustomer();
        assert($orderCustomer instanceof OrderCustomerEntity);
        $customer = $orderCustomer->getCustomer();
        assert($customer instanceof CustomerEntity);
        $customerAddresses = $customer->getAddresses();
        assert($customerAddresses instanceof CustomerAddressCollection);
        $orderLineItems = $order->getLineItems();
        assert($orderLineItems instanceof OrderLineItemCollection);
        $deliveries = $order->getDeliveries();
        assert($deliveries instanceof OrderDeliveryCollection);
        $delivery = $deliveries->first();
        
        $billingAddressId   = $order->getBillingAddressId();
        
        $this->logger->debug('creating sales channel with correct customer data', [
            SalesChannelContextService::CUSTOMER_ID => $customer->getId(),
            SalesChannelContextService::BILLING_ADDRESS_ID => $billingAddressId,
            SalesChannelContextService::PAYMENT_METHOD_ID => $abo->getPaymentMethodId(),
        ]);
        
        $cart = $this->cartService->createNew($salesChannelContext->getToken());
         
        $cart->addExtension('isRecurring', new ArrayStruct(['Recurring' => true]));
        
        if (!empty($abo->getDiscount()))
        {
			$cart->addExtension('discountType', new ArrayStruct(['discountType' => $abo->getDiscountType()]));
			
			if ( in_array($abo->getDiscountScope(), ['all', 'cycleduration']))
			{
				$cart->addExtension('discount', new ArrayStruct(['discount' => $abo->getDiscount()]));
			} elseif ($abo->getDiscountScope() == 'first' && !empty($abo->getTrialInterval()) && count($abo->get('subsOrders')->getElements()) == 2)
			{
				$cart->addExtension('discount', new ArrayStruct(['discount' => $abo->getDiscount()]));
			} elseif ($abo->getDiscountScope() == 'last' && ((empty($abo->getTrialInterval()) && count($abo->get('subsOrders')->getElements()) == $abo->getLength()) || (!empty($abo->getTrialInterval()) && count($abo->get('subsOrders')->getElements()) == ($abo->getLength() + 1))))
			{
				$cart->addExtension('discount', new ArrayStruct(['discount' => $abo->getDiscount()]));
			} elseif (empty($abo->getDiscountScope()) && empty($abo->getDiscountType())) 
			{
				$cart->addExtension('discount', new ArrayStruct(['discount' => $abo->getDiscount()]));
			}
		}

        $permissions = $salesChannelContext->getPermissions();
        $tempPermissions = $permissions;
        $subscriptionLineItem = 0;
        
        if (!empty($abo->getProductId()))
		{
			$freeDelivery = true;
			$product = $this->container->get('product.repository')->search(new Criteria([$abo->getProductId()]), $salesChannelContext->getContext())->first();
			
			if (!empty($abo->getShippingCalculateOnce()) || (method_exists($product, 'getStates') && !empty($product->getStates()) && $product->getStates()[0] ==  'is-download')) {
				$cart->addExtension('isNovalnetRecurring', new ArrayStruct(['shippingFree' => true]));
			}
			
			$lineItem = new LineItem(
                $product->getId(),
                'product',
                $product->getId(),
                !empty($abo->getQuantity()) ? $abo->getQuantity() : 1
            );
            
            if (empty($abo->getShippingCalculateOnce()) && method_exists($product, 'getStates') && !empty($product->getStates()) && $product->getStates()[0] !=  'is-download') {
				$freeDelivery = false;
			}
            
            $lineItem->setDeliveryInformation(new DeliveryInformation($abo->getQuantity(), 0, $freeDelivery));
            
            $lineItem->setStackable(true);
            
            $items[] = $lineItem;
            $cart = $this->cartService->add($cart, $items, $salesChannelContext);
		} else {
			 foreach ($orderLineItems as $orderLineItem) {
				if ($orderLineItem->getId() == $abo->getLineItemId() || $orderLineItem->gettype() == 'nn_discount') {
					$this->addLineItemToCart($abo, $cart, $orderLineItem, $salesChannelContext);
					$subscriptionLineItem++;
				}
			}
			
			if ($subscriptionLineItem == 0) {
				$this->logger->warning('no lineItem found for the subscription {subscriptionId}', ['subscriptionId' => $abo->getId()]);
				return null;
			}
		}
        
        $tempPermissions[ProductCartProcessor::SKIP_PRODUCT_RECALCULATION] = true;
        $tempPermissions[DeliveryProcessor::SKIP_DELIVERY_PRICE_RECALCULATION] = false;
        $tempPermissions[PromotionProcessor::SKIP_PROMOTION] = true;
        
        $salesChannelContext->setPermissions($tempPermissions);
		
        if (!empty($delivery)) {
            $cart->setDeliveries(new DeliveryCollection([new Delivery(
                new DeliveryPositionCollection(),
                new DeliveryDate($delivery->getShippingDateEarliest(), $delivery->getShippingDateLatest()),
                $delivery->getShippingMethod() ?? $salesChannelContext->getShippingMethod(),
                $salesChannelContext->getShippingLocation(),
                $delivery->getShippingCosts()
            )]));
        }
        $cart = $this->cartService->recalculate($cart, $salesChannelContext);
        $cartResponse = $this->cartOrderRoute->order($cart, $salesChannelContext, new RequestDataBag());
        $salesChannelContext->setPermissions($permissions);
        return $cartResponse->getOrder();
    }
    
    /**
     * add line items to cart
     *
     * @param $abo
     * @param Cart $cart
     * @param OrderLineItemEntity $orderLineItem
     * @param SalesChannelContext $salesChannelContext
     *
     * @return void
     */
    public function addLineItemToCart($abo, Cart $cart, OrderLineItemEntity $orderLineItem, SalesChannelContext $salesChannelContext): void
    {	
        $price = $orderLineItem->getPrice();
        $priceDefinition = $orderLineItem->getPriceDefinition();
        $payload = $orderLineItem->getPayload() ?? [];
        $freeDelivery = true;
        
        if (!empty($price)) {
            $taxId = $this->getTaxId($orderLineItem, $salesChannelContext);

            if (!empty($taxId)) {
                $priceDefinition = new QuantityPriceDefinition(
                    $price->getUnitPrice(),
                    $salesChannelContext->buildTaxRules($taxId),
                    $price->getQuantity()
                );

                $price = $this->quantityPriceCalculator->calculate($priceDefinition, $salesChannelContext);

                $payload['taxId'] = $taxId;
            }
        }
        
        $cartLineItem = new LineItem(
            $orderLineItem->getId(),
            (string)$orderLineItem->getType(),
            $orderLineItem->getReferencedId(),
            $orderLineItem->getQuantity()
        );
        
        if (!empty($abo->getShippingCalculateOnce()) || (method_exists($orderLineItem, 'getStates') && !empty($orderLineItem->getStates()) && $orderLineItem->getStates()[0] == 'is-download')) {
			$cart->addExtension('isNovalnetRecurring', new ArrayStruct(['shippingFree' => true]));
		}

        $cartLineItem->setCover($orderLineItem->getCover());
        $cartLineItem->setDescription($orderLineItem->getDescription());
        $cartLineItem->setGood($orderLineItem->getGood());
        $cartLineItem->setLabel($orderLineItem->getLabel());
        $cartLineItem->setPayload($payload);
        $cartLineItem->setPrice($price);
        $cartLineItem->setPriceDefinition($priceDefinition);
        $cartLineItem->setExtensions($orderLineItem->getExtensions());
        $cartLineItem->setStackable($orderLineItem->getStackable());
        $cartLineItem->setRemovable($orderLineItem->getRemovable());
        
        if (empty($abo->getShippingCalculateOnce()) && method_exists($orderLineItem, 'getStates') && !empty($orderLineItem->getStates()) && $orderLineItem->getStates()[0] != 'is-download') {
            $freeDelivery = false;
        }
        
        if ($orderLineItem->getType() != 'nn_discount') {
            $cartLineItem->setDeliveryInformation(new DeliveryInformation($orderLineItem->getQuantity(), 0, $freeDelivery));
        }
        
        $cart->add($cartLineItem);
    }
    
    /**
     * Get the tax ID.
     *
     * @param OrderLineItemEntity $orderLineItem
     * @param SalesChannelContext $salesChannelContext
     *
     * @return string|null
     */
    private function getTaxId(
        OrderLineItemEntity $orderLineItem,
        SalesChannelContext $salesChannelContext
    ): ?string {
        $taxId = null;

        if ($orderLineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE) {
            /** @var string $referenceId */
            $referenceId = $orderLineItem->getReferencedId();
            $criteria = new Criteria([$referenceId]);
            $product = $this->container->get('product.repository')->search($criteria, $salesChannelContext->getContext())->first();

            if ($product instanceof ProductEntity) {
                $taxId = $product->getTaxId();
            }
        }

        if (!empty($taxId)) {
            $payload = $orderLineItem->getPayload();
            $taxId = $payload['taxId'] ?? null;
        }

        return $taxId !== null ? (string)$taxId : null;
    }
    
    /**
     * Return the payment handler
     *
     * @param PaymentMethodEntity $paymentMethod
     *
     * @return string
     */
    public function getPaymentHandler(PaymentMethodEntity $paymentMethod): string
    {
        return $paymentMethod->getHandlerIdentifier();
    }
    
    /**
     * Send renewal reminder mail
     *
     * @param NovalnetSubscriptionEntity $subscription
     * @param Context $context
     *
     * @return void
     */
    public function sendRenewalReminder(NovalnetSubscriptionEntity $subscription, Context $context): void
    {
        $salesChannelEntity = $subscription->get('order')->getSalesChannel();
        $subsSettings = $this->getSubscriptionSettings($salesChannelEntity->getId());
        
        if (!empty($subsSettings['NovalnetSubscription.config.reminderEmail'])) {
            $this->logger->info('Sending the renewal reminder mail');
            $this->sendMail($subscription->getId(), $context, 'novalnet_renewal_reminder_mail');
        }
    }
    
    /**
     * Get checkout payment methods
     *
     * @param array $paymentMethodsIds
     * @param Context $context
     *
     * @return PaymentMethodCollection|null
     */
    public function getCheckoutPaymentMethods(array $paymentMethodsIds, Context $context): ?PaymentMethodCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $paymentMethodsIds))
                 ->addFilter(new EqualsFilter('active', true))
                 ->addSorting(new FieldSorting('position'))
                 ->addAssociation('media');
        return $this->container->get('payment_method.repository')->search($criteria, $context)->getEntities();
    }
    
    /**
     * Get interval length and period
     *
     * @param string $interval
     *
     * @return array
     */
    public function getIntervalPeriod($interval): array
    {
        return [CartHelper::getIntervalType($interval), CartHelper::getIntervalPeriod($interval)];
    }
    
    /**
     * Get Customer Billing Address
     *
     * @param $billingAddress
     *
     * @return string|null
     */
    public function getBillingAddressId($billingAddress) : ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new AndFilter([
            new EqualsFilter('customer_address.countryId', $billingAddress->getCountryId()),
            new EqualsFilter('customer_address.firstName', $billingAddress->getFirstName()),
            new EqualsFilter('customer_address.lastName', $billingAddress->getLastName()),
            new EqualsFilter('customer_address.zipcode', $billingAddress->getZipcode()),
            new EqualsFilter('customer_address.city', $billingAddress->getCity()),
            new EqualsFilter('customer_address.street', $billingAddress->getStreet()),
            new EqualsFilter('customer_address.company', $billingAddress->getCompany()),
        ]));
        
        $address = $this->customerAddressRepository->search($criteria, Context::createDefaultContext())->first();
        return !empty($address) ? $address->getId() : null;
    }
    
    /**
     * To fetch the shop language from order id.
     * Fixed language issue in translator.
     *
     * @param string $orderId
     *
     * @return string
     */
    public function getLocaleFromOrder(string $languageId): string
    {
        $languageCriteria = new Criteria([$languageId]);
        $languageCriteria->addAssociation('locale');
        $language  = $this->container->get('language.repository')->search($languageCriteria, Context::createDefaultContext())->first();
        $locale = $language->getLocale();
        if (!$locale) {
            return 'de-DE';
        }
        return in_array($locale->getCode(), ['de-DE', 'en-GB']) ? $locale->getCode() : 'de-DE';
    }
    
    /**
     * Set Session using key and data
     *
     * @param string $key
     * @param $
     *
     * @return void
     */
    public function setSession(string $key, $data): void
    {
        $this->requestStack->getSession()->set($key, $data);
    }
    
    /**
     * Get Session using key
     *
     * @param string $key
     */
    public function getSession(string $key)
    {
        return $this->requestStack->getSession()->get($key);
    }
    
    /**
     * Has Session using key
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasSession(string $key): bool
    {
        return $this->requestStack->getSession()->has($key);
    }
    
    /**
     * Remove Session
     *
     * @param string $key
     */
    public function removeSession(string $key)
    {
        $this->requestStack->getSession()->remove($key);
    }

    /**
     * Get the Novalnet subscription Handler
     *
     * @param string $paymentHandler
     *
     * @return string
     */
    public function getNovalnetPaymentHandler(string $paymentHandler): string
    {
        if (!empty($paymentHandler)) {
            $paymentMethod = preg_match('/\w+$/', $paymentHandler, $match);
            $match = $match[0];
            return $match;
        }
        return '';
    }
    
    /**
     * get the payment method
     * 
     * @return PaymentMethodEntity|null
     */
    public function getPaymentMethod(): ?PaymentMethodEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('payment_method.handlerIdentifier', 'Novalnet\NovalnetPayment\Service\NovalnetPayment'));
        /** @var PaymentMethodEntity|null $paymentMethod */
        $paymentMethod = $this->container->get('payment_method.repository')->search($criteria, Context::createDefaultContext())->first();
        return !empty($paymentMethod) ? $paymentMethod : null;
    }
    
    /**
     * get the parent product details
     * 
     * @param string $parentId
     * @param Context $context
     * 
     * @return ProductEntity|null
     */
    public function getParentProductId(string $parentId, Context $context): ?ProductEntity
    {
		return $this->container->get('product.repository')->search(new Criteria([$parentId]), $context)->first();
	}
	
	/*
     * Get Payment Method Novalnet payment
     *
     * @param Context $context
     * @param string|null $orderNumber
     *
     * @return string
     */
    public function getNovalnetPaymentName(Context $context, string $orderNumber): ?string
    {
        $connection = $this->container->get(Connection::class); 
        $novalnetTransaction = 'SELECT * FROM `novalnet_transaction_details` where order_no = '.$orderNumber.' ORDER BY created_at DESC';  
        $details = $connection->fetchAssociative($novalnetTransaction);
        if(!empty($details['additional_details'])){
			$data = json_decode($details['additional_details'], true);
			return !empty($data['payment_name']) ? $data['payment_name'] : '';
		}
        return '';
    }
    
    /*
     * Update the subscription for retry 
     *
     * @param NovalnetSubscriptionEntity $subscription
     * @param NovalnetSubsCycleEntity $cycle
     * @param SalesChannelContext $salesChannelContext
     * @param string|null $message
     *
     * @return void
     */
    public function updateRetrySubscription(NovalnetSubscriptionEntity $subscription, NovalnetSubsCycleEntity $cycle, SalesChannelContext $salesChannelContext, $message = null, $orderId = null)
    {
		if ($cycle->getStatus() == NovalnetSubscription::CYCLE_STATUS_RETRY || $cycle->getStatus() == NovalnetSubscription::CYCLE_STATUS_FAILURE) {
			$data = [
				'id'      => $cycle->getId(),
				'orderId' => $orderId,
				'status'  => NovalnetSubscription::CYCLE_STATUS_FAILURE
			];
                                
			// Update cancel status in subscription table
			$this->container->get('novalnet_subscription.repository')->upsert([[
				'id'    => $subscription->getId(),
				'status'    => NovalnetSubscription::SUBSCRIPTION_STATUS_CANCELLED,
				'cancelledAt' => date('Y-m-d H:i:s'),
				'cancelReason'  => $message
			]], $salesChannelContext->getContext());
			
			$this->sendMail($subscription->getId(), $salesChannelContext->getContext(), 'novalnet_cancellation_mail');
		} else {
            $data = [
                'id'       => $cycle->getId(),
                'orderId'  => $orderId,
                'status'   => NovalnetSubscription::CYCLE_STATUS_RETRY
            ];
                               
            $novalnetSettings = $this->getSubscriptionSettings($salesChannelContext->getSalesChannel()->getId());
			
			$retryDays = !empty($novalnetSettings['NovalnetSubscription.config.retryPayment']) ? $novalnetSettings['NovalnetSubscription.config.retryPayment'] : 1;
			$nextDate = $this->getFormattedDate($retryDays, 'days', $subscription->getNextDate()->format('Y-m-d H:i:s'));
								
            // Update cancel reason status in subscription table
            $this->container->get('novalnet_subscription.repository')->upsert([[
				'id'    => $subscription->getId(),
				'nextDate' => $nextDate
            ]], $salesChannelContext->getContext());
        }
        
        $this->container->get('novalnet_subs_cycle.repository')->upsert([array_filter($data)], $salesChannelContext->getContext());
	}
	
	/*
     * get all subscription products 
     *
     * @param Context $context
     *
     * @return null|array
     */
    public function getAllProductDetails(Context $context): ?array
    {
		$criteria = new Criteria();
        $criteria->addAssociation('visibilities.salesChannel');
        $criteria->addAssociation('configuratorSettings.option');
        $criteria->addAssociation('options');
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('novalnetConfiguration');
        return $this->container->get('product.repository')->search($criteria, $context)->getElements();
	}
	
	/*
     * get product from product ID
     *
     * @param string $productId
     * @param Context $context
     *
     * @return ProductEntity|null
     */
    public function getProduct(string $productId, Context $context): ?ProductEntity
    {
		$criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $productId));
        $criteria->addAssociation('configuratorSettings.option');
        $criteria->addAssociation('options');
        $criteria->addAssociation('options.group');
        $criteria->addAssociation('novalnetConfiguration');
        return $this->container->get('product.repository')->search($criteria, $context)->first();
	}
	
	/*
     * get product from product ID
     *
     * @param int $interval
     * @param string $unit
     *
     * 
     */
    public function getPeriod( int $interval, string $unit) 
    {
		
		$period = $interval.''.$unit;
		
		$periods = [
			'1d' => 'dailyDelivery',
			'1w' => 'weeklyDelivery',
			'2w' => 'Every2WeekDelivery',
			'3w' => 'Every3WeekDelivery',
			'1m' => 'monthlyDelivery',
			'6w' => 'Every6WeekDelivery',
			'2m' => 'Every2MonthDelivery',
			'3m' => 'Every3MonthDelivery',
			'4m' => 'Every4MonthDelivery',
			'6m' => 'halfYearlyDelivery',
			'9m' => 'Every9MonthDelivery',
			'1y' => 'yearlyDelivery'
        ];
        
        if (!empty($periods[$period])) {
            return $periods[$period];
        }
        return '';
	}
	
	/*
     * Get Active Subscription For End Customer
     *
     * @param SalesChannelContext $context
     * 
     * @return bool
     */
    public function getActiveSubscription(SalesChannelContext $context): bool
    {
		/* check if end customer is logged in or not */
		if (!is_null($context->getCustomer()))
		{
			$criteria = new Criteria();
			$criteria->addFilter(new AndFilter([
				new EqualsFilter('status', NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE),
				new EqualsFilter('customerId', $context->getCustomer()->getId()),
			]));
			$abo = $this->container->get('novalnet_subscription.repository')->search($criteria, $context->getContext())->getElements();
			if (count($abo) > 0)
			{
				return false;
			}
		}
		return true;
	}
	
}
