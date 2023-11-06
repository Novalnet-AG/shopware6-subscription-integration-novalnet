<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1637907042AddNovalnetSubscriptionTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1637907042;
    }

    public function update(Connection $connection): void
    {
        
        $query = <<<SQL
		CREATE TABLE IF NOT EXISTS `novalnet_product_config` (
			  `id` BINARY(16) NOT NULL,
			  `product_id` BINARY(16) NOT NULL,
			  `active` TINYINT(2) DEFAULT "0",
			  `type` VARCHAR(50) NOT NULL,
			  `display_name` VARCHAR(255) DEFAULT NULL,
			  `interval` tinyint(3) DEFAULT NULL,
			  `period` VARCHAR(50) DEFAULT NULL,
			  `subscription_length` INT(11) DEFAULT NULL,
			  `sign_up_fee`	double DEFAULT NULL,
			  `free_trial` INT(11) DEFAULT NULL,
			  `free_trial_period` VARCHAR(50) DEFAULT NULL,
			  `multiple_subscription` TINYINT(2) DEFAULT "0",
			  `multi_subscription_options` JSON DEFAULT NULL,
			  `operational_month` INT(11) DEFAULT 0,
			  `discount` double DEFAULT NULL,
			  `discount_scope` VARCHAR(50) DEFAULT NULL,
			  `discount_type` VARCHAR(50) DEFAULT NULL,
			  `detail_page_text` LONGTEXT DEFAULT NULL,
			  `predefined_selection` VARCHAR(50) DEFAULT NULL,
			  `discount_details` LONGTEXT DEFAULT NULL,
			  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
			  `updated_at` datetime DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Novalnet Product Configuration";
			
	
		CREATE TABLE IF NOT EXISTS `novalnet_subscription` (
				`id` binary(16) NOT NULL,
				`subs_number` INT(11) DEFAULT NULL COMMENT "Subscription Number",
				`order_id` binary(16) NOT NULL COMMENT "Intial Order ID",
				`line_item_id` binary(16) NOT NULL COMMENT "Order Line Item ID",
				`product_id` binary(16) NULL COMMENT "Product ID",
				`quantity` INT(3) DEFAULT NULL COMMENT "Order Line Item Quantity",
				`customer_id` binary(16) DEFAULT NULL COMMENT "Customer ID",
				`payment_method_id` binary(16) DEFAULT NULL COMMENT "Payment Method ID",
				`payment_reference` VARCHAR(255) DEFAULT NULL,
				`interval` tinyint(3) DEFAULT NULL,
				`unit` enum('d','m','y', 'w') DEFAULT NULL,
				`length` tinyint(3) DEFAULT NULL,
				`amount` double DEFAULT NULL,
				`discount` double DEFAULT NULL,
				`discount_scope` VARCHAR(50) DEFAULT NULL,
				`discount_type` VARCHAR(50) DEFAULT NULL,
				`status` enum ('ACTIVE', 'PENDING', 'PENDING_CANCEL', 'ON_HOLD', 'EXPIRED', 'SUSPENDED', 'CANCELLED')   DEFAULT NULL,
				`next_date` datetime DEFAULT NULL,
				`last_day_month` TINYINT(2) DEFAULT "0",
				`ending_at` datetime DEFAULT NULL,
				`trial_interval` tinyint(3) DEFAULT NULL,
				`trial_unit` enum('d','m','y', 'w') DEFAULT NULL,
				`cancelled_at` datetime DEFAULT NULL,
				`cancel_reason` text DEFAULT NULL,
				`date_change_reason` text DEFAULT NULL,
				`termination_date` datetime DEFAULT NULL,
				`canceled_by` binary(16) DEFAULT NULL COMMENT "Customer ID",
				`shipping_calculate_once` tinyint(1) DEFAULT NULL,
				`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
				`updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				CONSTRAINT `fk.novalnet_subscription.customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT `fk.novalnet_subscription.payment_method_id` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Novalnet Subscription Data";
		
		CREATE TABLE IF NOT EXISTS `novalnet_subs_cycle` (
				`id` binary(16) NOT NULL,
				`subs_id` binary(16) NOT NULL,
				`order_id` binary(16) NULL,
				`amount` double DEFAULT NULL,
				`interval` tinyint(3) DEFAULT NULL,
				`period` enum('d','m','y', 'w') DEFAULT NULL,
				`payment_method_id` binary(16) DEFAULT NULL,
				`cycles` tinyint(3) DEFAULT NULL,
				`cycle_date` datetime DEFAULT NULL,
				`status` enum ('SUCCESS','FAILURE','PENDING', 'RETRY')   DEFAULT NULL,
				`created_at` datetime DEFAULT CURRENT_TIMESTAMP,
				`updated_at` datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`),
				CONSTRAINT `fk.novalnet_subs_cycle.subs_id` FOREIGN KEY (`subs_id`) REFERENCES `novalnet_subscription` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Novalnet Subscription Cycles Data";
				
SQL;

            $connection->executeStatement($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
