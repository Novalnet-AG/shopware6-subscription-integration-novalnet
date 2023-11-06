<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1645700886ConfimationMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1645700886;
    }
    
    public function update(Connection $connection): void
    {
        // implement update
        $this->createConfirmationMailTemplate($connection);
    }
    
    public function createConfirmationMailTemplate(Connection $connection): void
    {
        $confirmationTemplateId = Uuid::randomBytes();
        $mailTypeId = $this->getMailTypeMapping()['novalnet_subs_confirm_mail']['id'];
        $deLangId = $enLangId = '';
        
        if ($this->fetchLanguageId('de-DE', $connection) != '') {
            $deLangId = Uuid::fromBytesToHex($this->fetchLanguageId('de-DE', $connection));
        }
        
        if ($this->fetchLanguageId('en-GB', $connection) != '') {
            $enLangId = Uuid::fromBytesToHex($this->fetchLanguageId('en-GB', $connection));
        }
            
        if (!$this->checkMailType($connection)) {
            $connection->insert(
                'mail_template_type',
                [
                'id' => Uuid::fromHexToBytes($mailTypeId),
                'technical_name' => 'novalnet_subs_confirm_mail',
                'available_entities' => json_encode($this->getMailTypeMapping()['novalnet_subs_confirm_mail']['availableEntities']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $connection->insert(
                'mail_template',
                [
                    'id' => $confirmationTemplateId,
                    'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                    'system_default' => 1,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            if ($enLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $confirmationTemplateId,
                        'language_id' => Uuid::fromHexToBytes($enLangId),
                        'subject' => 'Your Subscription order {{ subs.subsNumber }} is confirmed',
                        'description' => 'Subscription Confirmation Mail',
                        'sender_name' => '{{ salesChannel.name }}',
                        'content_html' => $this->getContentHtmlEn(),
                        'content_plain' => strip_tags($this->getContentHtmlEn()),
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
                
                $connection->insert(
                    'mail_template_type_translation',
                    [
                        'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                        'language_id' => Uuid::fromHexToBytes($enLangId),
                        'name' => 'Subscription Confirmation Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if ($deLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $confirmationTemplateId,
                        'language_id' => Uuid::fromHexToBytes($deLangId),
                        'subject' => 'Ihre Abonnementbestellung {{ subs.subsNumber }} ist bestätigt',
                        'description' => 'Bestätigungsmail für das Abonnement',
                        'sender_name' => '{{ salesChannel.name }}',
                        'content_html' => $this->getContentHtmlDe(),
                        'content_plain' => strip_tags($this->getContentHtmlDe()),
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );

                $connection->insert(
                    'mail_template_type_translation',
                    [
                        'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                        'language_id' => Uuid::fromHexToBytes($deLangId),
                        'name' => 'Bestätigungsmail für das Abonnement',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if (!in_array(Defaults::LANGUAGE_SYSTEM, [$enLangId, $deLangId])) {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $confirmationTemplateId,
                        'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                        'subject' => 'Your Subscription order {{ subs.subsNumber }} is confirmed',
                        'description' => 'Subscription Confirmation Mail',
                        'sender_name' => '{{ salesChannel.name }}',
                        'content_html' => $this->getContentHtmlEn(),
                        'content_plain' => strip_tags($this->getContentHtmlEn()),
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
                
                $connection->insert(
                    'mail_template_type_translation',
                    [
                        'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                        'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                        'name' => 'Subscription Confirmation Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
        }
    }
    
    private function getMailTypeMapping(): array
    {
        return[
            'novalnet_subs_confirm_mail' => [
                'id' => Uuid::randomHex(),
                'name' => 'Your Subscription order {{ subs.subsNumber }} is confirmed',
                'nameDe' => 'Ihre Abonnementbestellung {{ subs.subsNumber }} ist bestätigt',
                'availableEntities' => ['salesChannel' => 'sales_channel'],
            ],
        ];
    }
    
    private function fetchLanguageId(string $code, Connection $connection): ?string
    {
        /** @var string|null $langId */
        $langId = $connection->fetchOne('
        SELECT `language`.`id` FROM `language` INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id` WHERE `code` = :code LIMIT 1
        ', ['code' => $code]);

        if (!$langId) {
            return null;
        }

        return $langId;
    }

    private function checkMailType(Connection $connection): bool
    {
        $mailTypeId = $connection->fetchOne('
        SELECT `id` FROM `mail_template_type` WHERE `technical_name` = :technical_name LIMIT 1
        ', ['technical_name' => 'novalnet_subs_confirm_mail']);

        if (!$mailTypeId) {
            return false;
        }

        return true;
    }

    private function getContentHtmlEn(): string
    {
        return <<<MAIL
<div style="font-family:arial, sans-serif; font-size:12px;">
	{% set currencyIsoCode = order.currency.isoCode %}
	{{ order.orderCustomer.salutation.translated.letterName }} {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},<br>
	
	<br>

	Thank you for your order, Your subscription is confirmed with the Subscription number {{subs.subsNumber}} in {{ salesChannel.name }} on {{subs.updatedAt|date}}. 
	<br>
	<br>
	You will start receiving the subscription products automatically at selected intervals.
	<br>

	<br>
	<strong>Product Information:</strong><br>
	<br>

	<table width="100%" border="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
		<tr>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Prod. no.</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Description</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Quantities</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Price</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Total</strong></td>
		</tr>

		{% for lineItem in order.nestedLineItems %}
			{% set nestingLevel = 0 %}
			{% set nestedItem = lineItem %}
			{% block lineItem %}
			{% if lineItem.id == subs.lineItemId %}
				<tr>
					<td style="border-bottom:1px solid #cccccc;">{% if nestedItem.payload.productNumber is defined %}{{ nestedItem.payload.productNumber|u.wordwrap(80) }}{% endif %}</td>
					<td style="border-bottom:1px solid #cccccc;">
						{% if nestingLevel > 0 %}
							{% for i in 1..nestingLevel %}
								<span style="position: relative;">
									<span style="display: inline-block;
										position: absolute;
										width: 6px;
										height: 20px;
										top: 0;
										border-left:  2px solid rgba(0, 0, 0, 0.15);
										margin-left: {{ i * 10 }}px;"></span>
								</span>
							{% endfor %}
						{% endif %}

						<div>
							{{ nestedItem.label|u.wordwrap(80) }}
						</div>

						{% if nestedItem.payload.options is defined and nestedItem.payload.options|length >= 1 %}
							<div>
								{% for option in nestedItem.payload.options %}
									{{ option.group }}: {{ option.option }}
									{% if nestedItem.payload.options|last != option %}
										{{ " | " }}
									{% endif %}
								{% endfor %}
							</div>
						{% endif %}

						{% if nestedItem.payload.features is defined and nestedItem.payload.features|length >= 1 %}
							{% set referencePriceFeatures = nestedItem.payload.features|filter(feature => feature.type == 'referencePrice') %}
							{% if referencePriceFeatures|length >= 1 %}
								{% set referencePriceFeature = referencePriceFeatures|first %}
								<div>
									{{ referencePriceFeature.value.purchaseUnit }} {{ referencePriceFeature.value.unitName }}
									({{ referencePriceFeature.value.price|currency(currencyIsoCode) }}* / {{ referencePriceFeature.value.referenceUnit }} {{ referencePriceFeature.value.unitName }})
								</div>
							{% endif %}
						{% endif %}
					</td>
					<td style="border-bottom:1px solid #cccccc;">{{ nestedItem.quantity }}</td>
					<td style="border-bottom:1px solid #cccccc;">{{ nestedItem.unitPrice|currency(currencyIsoCode) }}</td>
					<td style="border-bottom:1px solid #cccccc;">{{ nestedItem.totalPrice|currency(currencyIsoCode) }}</td>
				</tr>
			{% endif %}

				{% if nestedItem.children.count > 0 %}
					{% set nestingLevel = nestingLevel + 1 %}
					{% for lineItem in nestedItem.children %}
						{% set nestedItem = lineItem %}
						{{ block('lineItem') }}
					{% endfor %}
				{% endif %}
			{% endblock %}
		{% endfor %}
	</table>
	
	<br>
	
	<br>
	<strong>Your subscription details:</strong><br>
	<br>
	
	<table width="100%" border="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
		<tr>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Interval</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Recurring Length</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Status</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Next Recurring Date</strong></td>
		</tr>
		<tr>
			<td style="border-bottom:1px solid #cccccc;">{{subs.interval}} {% if subs.unit == 'd' %}Day(s){% elseif subs.unit == 'w' %} Week(s) {% elseif subs.unit == 'm' %} Month(s) {% else %} Year(s) {% endif %} </td>
			<td style="border-bottom:1px solid #cccccc;">{% if subs.length == 0 %} unlimited term {% else %}{{subs.length}} {% if subs.unit == 'd' %}Day(s){% elseif subs.unit == 'w' %} Week(s) {% elseif subs.unit == 'm' %} Month(s) {% else %} Year(s) {% endif %}{% endif %}</td>
			<td style="border-bottom:1px solid #cccccc;">{{subs.status}}</td>
			<td style="border-bottom:1px solid #cccccc;">{{subs.nextDate|date("d/m/Y")}}</td>
		</tr>
	</table>

	<p>
		{% set delivery = order.deliveries.first %}
		<br>
		
		<strong>Selected payment type:</strong> {{ order.transactions.first.paymentMethod.name }}<br>
		{{ order.transactions.first.paymentMethod.description }}<br>
		<br>

		<strong>Selected shipping type:</strong> {{ delivery.shippingMethod.translated.name }}<br>
		{{ delivery.shippingMethod.translated.description }}<br>
		<br>

		{% set billingAddress = order.addresses.get(order.billingAddressId) %}
		<strong>Billing address:</strong><br>
		{{ billingAddress.company }}<br>
		{{ billingAddress.firstName }} {{ billingAddress.lastName }}<br>
		{{ billingAddress.street }} <br>
		{{ billingAddress.zipcode }} {{ billingAddress.city }}<br>
		{{ billingAddress.country.translated.name }}<br>
		<br>

		<strong>Shipping address:</strong><br>
		{{ delivery.shippingOrderAddress.company }}<br>
		{{ delivery.shippingOrderAddress.firstName }} {{ delivery.shippingOrderAddress.lastName }}<br>
		{{ delivery.shippingOrderAddress.street }} <br>
		{{ delivery.shippingOrderAddress.zipcode}} {{ delivery.shippingOrderAddress.city }}<br>
		{{ delivery.shippingOrderAddress.country.translated.name }}<br>
		<br>
		{% if order.orderCustomer.vatIds %}
			Your VAT-ID: {{ order.orderCustomer.vatIds|first }}
			In case of a successful order and if you are based in one of the EU countries, you will receive your goods exempt from turnover tax.<br>
		{% endif %}
		<br>


		You can check the current status of your subscription on our website under "My account" - "My subscriptions" anytime: {{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}<br>
		<br>
		
		If you have any questions, do not hesitate to contact us.

	</p>
	<br>
</div>
MAIL;
    }

    private function getContentHtmlDe(): string
    {
        return <<<MAIL
<div style="font-family:arial, sans-serif; font-size:12px;">
	{% set currencyIsoCode = order.currency.isoCode %}
	{{ order.orderCustomer.salutation.translated.letterName }} {{ order.orderCustomer.firstName }} {{ order.orderCustomer.lastName }},<br>
	<br>

	Vielen Dank für Ihre Bestellung, Ihr Abonnement ist mit der Abonnementnummer {{subs.subsNumber}} in {{ salesChannel.name }} bestätigt am {{subs.updatedAt|date}}. 
	<br>
	<br>
	Sie erhalten die abonnierten Produkte automatisch in ausgewählten Abständen.
	<br>

	<br>
	<strong>Informationen zum Produkt:</strong><br>
	<br>

	<table width="100%" border="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
		<tr>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Produkt-Nr.</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Bezeichnung</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Menge</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Preis</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Summe</strong></td>
		</tr>

		{% for lineItem in order.nestedLineItems %}
			{% set nestingLevel = 0 %}
			{% set nestedItem = lineItem %}
			{% block lineItem %}
			{% if lineItem.id == subs.lineItemId %}
				<tr>
					<td style="border-bottom:1px solid #cccccc;">{% if nestedItem.payload.productNumber is defined %}{{ nestedItem.payload.productNumber|u.wordwrap(80) }}{% endif %}</td>
					<td style="border-bottom:1px solid #cccccc;">
						{% if nestingLevel > 0 %}
							{% for i in 1..nestingLevel %}
								<span style="position: relative;">
									<span style="display: inline-block;
										position: absolute;
										width: 6px;
										height: 20px;
										top: 0;
										border-left:  2px solid rgba(0, 0, 0, 0.15);
										margin-left: {{ i * 10 }}px;"></span>
								</span>
							{% endfor %}
						{% endif %}

						<div>
							{{ nestedItem.label|u.wordwrap(80) }}
						</div>

						{% if nestedItem.payload.options is defined and nestedItem.payload.options|length >= 1 %}
							<div>
								{% for option in nestedItem.payload.options %}
									{{ option.group }}: {{ option.option }}
									{% if nestedItem.payload.options|last != option %}
										{{ " | " }}
									{% endif %}
								{% endfor %}
							</div>
						{% endif %}

						{% if nestedItem.payload.features is defined and nestedItem.payload.features|length >= 1 %}
							{% set referencePriceFeatures = nestedItem.payload.features|filter(feature => feature.type == 'referencePrice') %}
							{% if referencePriceFeatures|length >= 1 %}
								{% set referencePriceFeature = referencePriceFeatures|first %}
								<div>
									{{ referencePriceFeature.value.purchaseUnit }} {{ referencePriceFeature.value.unitName }}
									({{ referencePriceFeature.value.price|currency(currencyIsoCode) }}* / {{ referencePriceFeature.value.referenceUnit }} {{ referencePriceFeature.value.unitName }})
								</div>
							{% endif %}
						{% endif %}
					</td>
					<td style="border-bottom:1px solid #cccccc;">{{ nestedItem.quantity }}</td>
					<td style="border-bottom:1px solid #cccccc;">{{ nestedItem.unitPrice|currency(currencyIsoCode) }}</td>
					<td style="border-bottom:1px solid #cccccc;">{{ nestedItem.totalPrice|currency(currencyIsoCode) }}</td>
				</tr>
			{% endif %}

				{% if nestedItem.children.count > 0 %}
					{% set nestingLevel = nestingLevel + 1 %}
					{% for lineItem in nestedItem.children %}
						{% set nestedItem = lineItem %}
						{{ block('lineItem') }}
					{% endfor %}
				{% endif %}
			{% endblock %}
		{% endfor %}
	</table>
	<br>
	<br>
	<strong>Details zu Ihrem Abonnement:</strong><br>
	<br>

	<table width="100%" border="0" style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
		<tr>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Intervall</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Wiederkehrende Länge</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Zustand</strong></td>
			<td bgcolor="#F7F7F2" style="border-bottom:1px solid #cccccc;"><strong>Nächster wiederkehrender Termin</strong></td>
		</tr>
		<tr>
			<td style="border-bottom:1px solid #cccccc;">{{subs.interval}} {% if subs.unit == 'd' %}Tag(e){% elseif subs.unit == 'w' %} Woche(n) {% elseif subs.unit == 'm' %} Monat(e) {% else %} Jahr(e) {% endif %} </td>
			<td style="border-bottom:1px solid #cccccc;">{% if subs.length == 0 %} unbegrenzte Laufzeit {% else %}{{subs.length}} {% if subs.unit == 'd' %}Tag(e){% elseif subs.unit == 'w' %} Woche(n) {% elseif subs.unit == 'm' %} Monat(e) {% else %} Jahr(e) {% endif %}{% endif %}</td>
			<td style="border-bottom:1px solid #cccccc;">{{subs.status}}</td>
			<td style="border-bottom:1px solid #cccccc;">{{subs.nextDate|date("d/m/Y")}}</td>
		</tr>
	</table>
	
	<p>
		{% set delivery = order.deliveries.first %}
		<br>
		
		<strong>Ausgewählte Zahlungsart:</strong> {{ order.transactions.first.paymentMethod.name }}<br>
		{{ order.transactions.first.paymentMethod.description }}<br>
		<br>

		<strong>Gewählte Versandart:</strong> {{ delivery.shippingMethod.translated.name }}<br>
		{{ delivery.shippingMethod.translated.description }}<br>
		<br>

		{% set billingAddress = order.addresses.get(order.billingAddressId) %}
		<strong>Rechnungsadresse:</strong><br>
		{{ billingAddress.company }}<br>
		{{ billingAddress.firstName }} {{ billingAddress.lastName }}<br>
		{{ billingAddress.street }} <br>
		{{ billingAddress.zipcode }} {{ billingAddress.city }}<br>
		{{ billingAddress.country.translated.name }}<br>
		<br>

		<strong>Lieferadresse:</strong><br>
		{{ delivery.shippingOrderAddress.company }}<br>
		{{ delivery.shippingOrderAddress.firstName }} {{ delivery.shippingOrderAddress.lastName }}<br>
		{{ delivery.shippingOrderAddress.street }} <br>
		{{ delivery.shippingOrderAddress.zipcode}} {{ delivery.shippingOrderAddress.city }}<br>
		{{ delivery.shippingOrderAddress.country.translated.name }}<br>
		<br>
		{% if order.orderCustomer.vatIds %}
			Ihre Umsatzsteuer-ID: {{ order.orderCustomer.vatIds|first }}
			Bei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland bestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit.<br>
		{% endif %}
		<br>

		Sie können den aktuellen Status Ihres Abonnements jederzeit auf unserer Website unter "Meine Konto" - "Meine Abonnements" überprüfen.: {{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}<br>
		<br>
    
		Wenn Sie Fragen haben, zögern Sie nicht, uns zu kontaktieren.

	</p>
	<br>
</div>
MAIL;
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
