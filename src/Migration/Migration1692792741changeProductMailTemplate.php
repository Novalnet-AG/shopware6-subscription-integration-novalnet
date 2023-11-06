<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1692792741changeProductMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1692792741;
    }

    public function update(Connection $connection): void
    {
        // implement update
        $this->createProductChangeTemplate($connection);
    }
    
    public function createProductChangeTemplate(Connection $connection): void
    {
		$cancellationId = Uuid::randomBytes();
        $mailTypeId = $this->getMailTypeMapping()['novalnet_product_change_mail']['id'];
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
                'technical_name' => 'novalnet_product_change_mail',
                'available_entities' => json_encode($this->getMailTypeMapping()['novalnet_product_change_mail']['availableEntities']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $connection->insert(
                'mail_template',
                [
                    'id' => $cancellationId,
                    'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                    'system_default' => 1,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            if ($enLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $cancellationId,
                        'language_id' => Uuid::fromHexToBytes($enLangId),
                        'subject' => 'The product has been changed for your subscription order number {{ subs.subsNumber }}',
                        'description' => 'Subscription Product Changed Mail',
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
                        'name' => 'Subscription Product Changed Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if ($deLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $cancellationId,
                        'language_id' => Uuid::fromHexToBytes($deLangId),
                        'subject' => 'Das Produkt hat sich für Ihre Abonnement-Bestellnummer geändert {{ subs.subsNumber }}',
                        'description' => 'Abonnement Produkt Geänderte Mail',
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
                        'name' => 'Abonnement Produkt Geänderte Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if (!in_array(Defaults::LANGUAGE_SYSTEM, [$enLangId, $deLangId])) {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $cancellationId,
                        'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                        'subject' => 'The product has been changed for your subscription order number {{ subs.subsNumber }}',
                        'description' => 'Subscription Product Changed Mail',
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
                        'name' => 'Subscription Product Changed Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
        }
	}
	
	private function getMailTypeMapping(): array
    {
        return[
            'novalnet_product_change_mail' => [
                'id' => Uuid::randomHex(),
                'name' => 'Subscription Product Changed Mail',
                'nameDe' => 'Abonnement Produkt Geänderte Mail',
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
        ', ['technical_name' => 'novalnet_product_change_mail']);

        if (!$mailTypeId) {
            return false;
        }

        return true;
    }
    
    private function getContentHtmlEn(): string
    {
        return <<<MAIL
<div style="font-family:Arial, Helvetica, sans-serif; font-size:12px;">
	<p>
		{% set currencyIsoCode = order.currency.isoCode %}
		{{order.orderCustomer.salutation.letterName }} {{order.orderCustomer.firstName}} {{order.orderCustomer.lastName}},<br>
		<br>
		The product has beed changed from <b>{{ previousProductName }}</b> to <b>{{ currentProductName }}</b> with the quantity {{ subs.quantity }} for your subscription (Subscription Number: {{subs.subsNumber}}) from the shop {{ salesChannel.name }}.<br>
		<br>
		To view the status of your subscription order. <a href="{{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}"> click here</a>
		<br>
		<br>
		Hereafter, the recurring order will be processed with updated product details.
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
		Das Produkt wurde geändert von <b>{{ previousProductName }}</b> zu <b>{{ currentProductName }}</b> mit der Menge {{ subs.quantity }} für Ihr Abonnement (Abonnementnummer: {{subs.subsNumber}}) aus dem Geschäft {{ salesChannel.name }}.<br>
		<br>
		Um den Status Ihres Abonnements einzusehen, <a href="{{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}"> klicken Sie bitte hier</a>
		<br>
		<br>
		Danach wird die wiederkehrende Bestellung mit aktualisierten Produktdaten bearbeitet.
		<br>
		<br>
		Für weitere Informationen können Sie uns gerne kontaktieren.
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
