<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1646040887ReactivationMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1646040887;
    }

    public function update(Connection $connection): void
    {
        $this->createReactivationMailTemplate($connection);
    }
    
    public function createReactivationMailTemplate(Connection $connection): void
    {
        $activeTemplateId = Uuid::randomBytes();
        $mailTypeId = $this->getMailTypeMapping()['novalnet_reactive_mail']['id'];
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
                'technical_name' => 'novalnet_reactive_mail',
                'available_entities' => json_encode($this->getMailTypeMapping()['novalnet_reactive_mail']['availableEntities']),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            $connection->insert(
                'mail_template',
                [
                    'id' => $activeTemplateId,
                    'mail_template_type_id' => Uuid::fromHexToBytes($mailTypeId),
                    'system_default' => 1,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );

            if ($enLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $activeTemplateId,
                        'language_id' => Uuid::fromHexToBytes($enLangId),
                        'subject' => 'Your Subscription order {{ subs.subsNumber }} is reactivated',
                        'description' => 'Subscription Reactivation Mail',
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
                        'name' => 'Subscription Reactivation Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if ($deLangId != '') {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $activeTemplateId,
                        'language_id' => Uuid::fromHexToBytes($deLangId),
                        'subject' => 'Ihr Abonnementauftrag {{ subs.subsNumber }} ist reaktiviert',
                        'description' => 'Mail zur Reaktivierung des Abonnements',
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
                        'name' => 'Mail zur Reaktivierung des Abonnements',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
            
            if (!in_array(Defaults::LANGUAGE_SYSTEM, [$enLangId, $deLangId])) {
                $connection->insert(
                    'mail_template_translation',
                    [
                        'mail_template_id' => $activeTemplateId,
                        'language_id' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
                        'subject' => 'Your Subscription order {{ subs.subsNumber }} is reactivated',
                        'description' => 'Subscription Reactivation Mail',
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
                        'name' => 'Subscription Reactivation Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
        }
    }
    
    private function getMailTypeMapping(): array
    {
        return[
            'novalnet_reactive_mail' => [
                'id' => Uuid::randomHex(),
                'name' => 'Subscription Reactivation Information',
                'nameDe' => 'Informationen zur Reaktivierung von Abonnements',
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
        ', ['technical_name' => 'novalnet_reactive_mail']);

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
		We want to inform you that the subscription for (Subscription Number: {{subs.subsNumber}}) from {{ salesChannel.name }} has been reactivated on {{ subs.updatedAt|date }}.<br>
		<br>
		To view the status of your subscription order. <a href="{{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}"> click here</a>
		<br>
		<br>
		You will now receive the subscription products at selected intervals automatically.
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
		Wir möchten Sie darüber informieren, dass das Abonnement für (Abonnementnummer: {{subs.subsNumber}}) von {{ salesChannel.name }} am {{ subs.updatedAt|date }} reaktiviert worden ist.<br>
		<br>
		Um den Status Ihres Abonnements einzusehen, <a href="{{ rawUrl('frontend.novalnet.subscription.orders.detail', { 'aboId': subs.id }, salesChannel.domains|first.url) }}"> klicken Sie bitte hier</a>
		<br>
		<br>
		Sie erhalten die Abo-Produkte nun automatisch in ausgewählten Abständen.
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
