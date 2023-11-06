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

namespace Novalnet\NovalnetSubscription\Installer;

use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;

/**
 * PaymentMethodInstaller Class.
 */
class PluginInstaller
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var ContainerInterface
     */
    private $container;
    
    /**
     * @var EntityRepository
     */
    private $mailTemplateRepo;
    
    /**
     * @var EntityRepository
     */
    private $mailTemplateTypeRepo;
    
    /**
     * @var array
     */
    private $novalnetSupportedPayments = ['NovalnetCreditCard', 'NovalnetSepa', 'NovalnetInvoice', 'NovalnetPrepayment', 'NovalnetInvoiceGuarantee', 'NovalnetSepaGuarantee', 'NovalnetGooglePay', 'NovalnetApplePay', 'NovalnetPayment'];
    
    /**
     * @var string
     */
    private $defaultPaymentType = 'Shopware\Core\Checkout\Payment\Cart\PaymentHandler';

    /**
     * Constructs a `PaymentMethodInstaller`
     *
     * @param ContainerInterface $container
     * @param Context $context
     */
    public function __construct(ContainerInterface $container, Context $context)
    {
        $this->context   = $context;
        $this->container = $container;
        $this->mailTemplateRepo = $this->container->get('mail_template.repository');
        $this->mailTemplateTypeRepo = $this->container->get('mail_template_type.repository');
    }

    /**
     * Add Payment Methods on plugin installation
     *
     */
    public function install(): void
    {
        $this->addNumberRange();
        $connection = $this->container->get(Connection::class);
        $this->createRenewalMailTemplate($connection);
    }
    
    /**
     * Add default value for subscription supported payment
     *
     */
    public function activate(): void
    {
        $this->addDefaultValue();
    }
    
    /**
     * Add Payment Methods on plugin update process
     *
     */
    public function update(): void
    {
        $connection = $this->container->get(Connection::class);
        $this->createRenewalMailTemplate($connection);
    }
    
    /**
     * remove the subscription configuration during the uninstallation.
     *
     */
    public function uninstall(): void
    {
        $connection = $this->container->get(Connection::class);
        
        if (method_exists($connection, 'executeStatement')) {
            $connection->executeStatement("DROP TABLE IF EXISTS novalnet_product_config;");
            $connection->executeStatement("DROP TABLE IF EXISTS novalnet_subs_cycle;");
            $connection->executeStatement("DROP TABLE IF EXISTS novalnet_subscription;");
        } else {
            $connection->exec("DROP TABLE IF EXISTS novalnet_product_config;");
            $connection->exec("DROP TABLE IF EXISTS novalnet_subs_cycle;");
            $connection->exec("DROP TABLE IF EXISTS novalnet_subscription;");
        }
        
        $mailTemplate = ['novalnet_subs_confirm_mail', 'novalnet_cancellation_mail', 'novalnet_reactive_mail', 'novalnet_suspend_mail', 'novalnet_renewal_mail', 'novalnet_renewal_reminder_mail', 'novalnet_remaing_cycle_date_change_mail', 'novalnet_product_downgrade_mail', 'novalnet_product_change_mail', 'novalnet_product_upgrade_mail', 'novalnet_payment_change_mail'];
        /** @var EntityRepository $mailTemplateRepo */
        $mailTemplateRepo = $this->container->get('mail_template.repository');
        /** @var EntityRepository $mailTemplateTypeRepo */
        $mailTemplateTypeRepo = $this->container->get('mail_template_type.repository');
        
        foreach ($mailTemplate as $template) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $template));
            
            $mailData = $mailTemplateRepo->search($criteria, $this->context)->first();
            
            if ($mailData) {
                // delete subscription mail template
                $mailTemplateRepo->delete([['id' => $mailData->getId()]], $this->context);
                $mailTemplateTypeRepo->delete([['id' => $mailData->getMailTemplateTypeId()]], $this->context);
            }
        }
    }
    
    /**
     * Add Novalnet Subscription Order Number Range
     *
     */
    private function addNumberRange(): void
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', NovalnetSubscription::SUBSCRIPTION_NUMBER_RANGE_TECHNICAL_NAME));
        
        /** @var EntityRepository$numberRangeRepository */
        $numberRangeRepository = $this->container->get('number_range.repository');
        /** @var EntityRepository $numberRangeTypeRepository */
        $numberRangeTypeRepository = $this->container->get('number_range_type.repository');
        
        $result = $numberRangeTypeRepository->searchIds($criteria, $this->context);
        
        if ($result->firstId() !== null) {
            return;
        }
        
        $data = [
            'name' => 'NovalnetSubscription',
            'global' => true,
            'pattern' => '{n}',
            'start' => 10000,
            'type' => [
                'typeName' => 'NovalnetSubscription',
                'technicalName' => NovalnetSubscription::SUBSCRIPTION_NUMBER_RANGE_TECHNICAL_NAME,
                'global' => false,
            ]
        ];
        
        $numberRangeRepository->upsert([$data], $this->context);
    }
    
    /**
     * Add Subscription Supported Payments
     *
     */
    private function addDefaultValue(): void
    {
        $paymentMethodRepository = $this->container->get('payment_method.repository');
        $supportedPayments = [];
        
        foreach ($this->novalnetSupportedPayments as $paymentMethodHandler) {
            // check if Novalnet subscription is exists
            $criteria   = new Criteria();
            $criteria->addFilter(new AndFilter([
                new ContainsFilter('handlerIdentifier', $paymentMethodHandler),
                new EqualsFilter('active', 1)
            ]));
            
            $id = $paymentMethodRepository->searchIds($criteria, $this->context)->firstId();
            if (!empty($id)) {
                array_push($supportedPayments, $id);
            }
        }
        
        if (empty($supportedPayments)) {
            // check if default payment is exists
            $criteria   = new Criteria();
            $criteria->addFilter(new AndFilter([
                new ContainsFilter('handlerIdentifier', $this->defaultPaymentType),
                new EqualsFilter('active', 1)
            ]));
            $supportedPayments = $paymentMethodRepository->searchIds($criteria, $this->context)->getIds();
        }
        
        $this->container->get(SystemConfigService::class)->set('NovalnetSubscription.config.supportedPayments', $supportedPayments);
    }
    
    public function createRenewalMailTemplate(Connection $connection): void
    {
        
        $mailType = $this->checkMailType();
        $mailTemplateTypeId = Uuid::randomHex();
        $mailTemplateId = Uuid::randomHex();

        if (!is_null($mailType)) {
            $mailTemplateId = $mailType->getId();
            $mailTemplateTypeId = $mailType->getMailTemplateTypeId();
        }
        
        
        $this->mailTemplateRepo->upsert([
            [
                'id' => $mailTemplateId,
                'translations' => [
                    'de-DE' => [
                        'subject' => 'Ihr nächstes Zyklusdatum wurde erfolgreich geändert',
                        'contentHtml' => $this->getContentHtmlDe(),
                        'contentPlain'=> strip_tags($this->getContentHtmlDe()),
                        'description' => 'Novalnet Bestellbestätigung',
                        'senderName'  => '{{ salesChannel.name }}',
                    ],
                    'en-GB' => [
                        'subject' => 'Your Next Cycle Date Changed successfully',
                        'contentHtml' => $this->getContentHtmlEn(),
                        'contentPlain'=> strip_tags($this->getContentHtmlEn()),
                        'description' => 'Next Cycle Date Change Mail',
                        'senderName'  => '{{ salesChannel.name }}',
                    ],
                ],
                'mailTemplateType' => [
                    'id' => $mailTemplateTypeId,
                    'technicalName' => 'novalnet_remaing_cycle_date_change_mail',
                    'translations'  => [
                        'de-DE' => [
                            'name' => 'Nächster Zyklus Datum ändern Mail',
                        ],
                        'en-GB' => [
                            'name' => 'Next Cycle Date Change Mail',
                        ],
                    ],
                    'availableEntities' => [
                        'salesChannel' => 'sales_channel',
                    ],
                ],
            ]
        ], $this->context);
    }
        

    private function checkMailType():?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', 'novalnet_remaing_cycle_date_change_mail'));
        $mailTypeId = $this->mailTemplateRepo->search($criteria, $this->context)->first();
        return $mailTypeId;
    }

    private function getContentHtmlEn(): string
    {
        return <<<MAIL
<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
	<p>
		{% set currencyIsoCode = order.currency.isoCode %}
		{{order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},<br>
		<br>
		We would like to inform you that your subscription next cycle date has been changed successfully to {{ subs.nextDate|date('Y-m-d') }} .<br>
		<br>
		To view the status of your subscription orders. <a href="{{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}"> click here</a>
		<br>
		<br>
		
		For further information, please get in touch with us.

	</p>
	<br>
</div>
MAIL;
    }

    private function getContentHtmlDe(): string
    {
        return <<<MAIL
<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
	<p>
		{% set currencyIsoCode = order.currency.isoCode %}
		{{order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},<br>
		<br>
		Wir möchten Sie darüber informieren, dass das Datum des nächsten Zyklus Ihres Abonnements erfolgreich auf {{ subs.nextDate|date('Y-m-d') }} geändert wurde.<br>
		<br>
		Um den Status Ihres Abonnements einzusehen, <a href="{{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}"> klicken Sie bitte hier</a>
		<br>
		<br>
		
		Für weitere Informationen können Sie uns gerne kontaktieren.

	</p>
	<br>
</div>
MAIL;
    }
}
