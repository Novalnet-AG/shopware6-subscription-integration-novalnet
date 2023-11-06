<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1648115983RenewalMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1648115983;
    }

    public function update(Connection $connection): void
    {
        $this->createRenewalMailTemplate($connection);
    }
    
    public function createRenewalMailTemplate(Connection $connection): void
    {
        $cancellationId = Uuid::randomBytes();
        $mailTypeId = $this->getMailTypeMapping()['novalnet_renewal_mail']['id'];
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
                'technical_name' => 'novalnet_renewal_mail',
                'available_entities' => json_encode($this->getMailTypeMapping()['novalnet_renewal_mail']['availableEntities']),
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
                        'subject' => 'Your Subscription order {{ subs.subsNumber }} is renewed successfully',
                        'description' => 'Subscription Renewal Mail',
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
                        'name' => 'Subscription Renewal Mail',
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
                        'subject' => 'Ihre Abonnementbestellung {{ subs.subsNumber }} wurde erfolgreich verlängert',
                        'description' => 'Mail zur Abonnementserneuerung',
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
                        'name' => 'Mail zur Abonnementserneuerung',
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
                        'subject' => 'Your Subscription order {{ subs.subsNumber }} is renewed successfully',
                        'description' => 'Subscription Renewal Mail',
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
                        'name' => 'Subscription Renewal Mail',
                        'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]
                );
            }
        }
    }
    
    private function getMailTypeMapping(): array
    {
        return[
            'novalnet_renewal_mail' => [
                'id' => Uuid::randomHex(),
                'name' => 'Subscription Renewal Mail',
                'nameDe' => 'Mail zur Abonnementserneuerung',
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
        ', ['technical_name' => 'novalnet_renewal_mail']);

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
		We want to inform you that the subscription for (Subscription Number: {{subs.subsNumber}}) from {{ salesChannel.name }} has been renewed successfully on the date {{subs.updatedAt|date('Y-m-d')}}.<br>
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
		Wir möchten Sie darüber informieren, dass das Abonnement für (Abonnementnummer: {{subs.subsNumber}}) von {{ salesChannel.name }} erfolgreich zum folgenden Datum verlängert wurde {{subs.updatedAt|date('Y-m-d')}}.<br>
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
