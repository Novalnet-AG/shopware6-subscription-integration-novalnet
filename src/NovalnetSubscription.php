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

namespace Novalnet\NovalnetSubscription;

use Doctrine\DBAL\Connection;
use Novalnet\NovalnetSubscription\Installer\PluginInstaller;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * NovalnetSubscription Class.
 */
class NovalnetSubscription extends Plugin
{
    public const SUBSCRIPTION_NUMBER_RANGE_TECHNICAL_NAME = 'novalnet_subscription';
    
    /* subscription status */
    public const SUBSCRIPTION_STATUS_SUSPENDED      = 'SUSPENDED';
    public const SUBSCRIPTION_STATUS_CANCELLED      = 'CANCELLED';
    public const SUBSCRIPTION_STATUS_ACTIVE         = 'ACTIVE';
    public const SUBSCRIPTION_STATUS_ON_HOLD        = 'ON_HOLD';
    public const SUBSCRIPTION_STATUS_PENDING        = 'PENDING';
    public const SUBSCRIPTION_STATUS_PENDING_CANCEL = 'PENDING_CANCEL';
    public const SUBSCRIPTION_STATUS_EXPIRED        = 'EXPIRED';
    
    /* cycle status */
    public const CYCLE_STATUS_SUCCESS = 'SUCCESS';
    public const CYCLE_STATUS_PENDING = 'PENDING';
    public const CYCLE_STATUS_CANCELLED = 'CANCELLED';
    public const CYCLE_STATUS_FAILURE = 'FAILURE';
    public const CYCLE_STATUS_RETRY   = 'RETRY';
    
    /* multiple subscription interval */
    public const INTERVAL_TYPE_DAILY = 'dailyDelivery';
    public const INTERVAL_TYPE_WEEKLY = 'weeklyDelivery';
    public const INTERVAL_TYPE_WEEKS_2 = 'Every2WeekDelivery';
    public const INTERVAL_TYPE_WEEKS_3 = 'Every3WeekDelivery';
    public const INTERVAL_TYPE_WEEKS_4 = 'Every4WeekDelivery';
    public const INTERVAL_TYPE_WEEKS_6 = 'Every6WeekDelivery';
    public const INTERVAL_TYPE_MONTHLY = 'monthlyDelivery';
    public const INTERVAL_TYPE_MONTHS_2 = 'Every2MonthDelivery';
    public const INTERVAL_TYPE_MONTHS_3 = 'Every3MonthDelivery';
    public const INTERVAL_TYPE_MONTHS_4 = 'Every4MonthDelivery';
    public const INTERVAL_TYPE_HALF_YEARLY = 'halfYearlyDelivery';
    public const INTERVAL_TYPE_MONTHS_9 = 'Every9MonthDelivery';
    public const INTERVAL_TYPE_YEARLY = 'yearlyDelivery';
    
    /**
     * Builds a `NovalnetSubscription` plugin.
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    /**
     * Plugin installation process
     *
     * @param InstallContext $installContext
     */
    public function install(InstallContext $installContext): void
    {
		// Alter the subscription tables
        $this->alterSubscriptionTable();
        
        (new PluginInstaller($this->container, $installContext->getContext()))->install();
        
        parent::install($installContext);
    }
    
    /**
     * Plugin activation process
     *
     * @param ActivateContext $activateContext
     */
    public function activate(ActivateContext $activateContext): void
    {
        (new PluginInstaller($this->container, $activateContext->getContext()))->activate();
        parent::activate($activateContext);
    }
    
    /**
     * Plugin uninstall process
     *
     * @param UninstallContext $uninstallContext
     */
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            (new PluginInstaller($this->container, $uninstallContext->getContext()))->uninstall();
        }
        parent::uninstall($uninstallContext);
    }
    
    /**
     * Plugin update process
     *
     * @param UpdateContext $updateContext
     */
    public function update(UpdateContext $updateContext): void
    {
		// Alter the subscription tables
        $this->alterSubscriptionTable();
        
        (new PluginInstaller($this->container, $updateContext->getContext()))->update();
        
        parent::update($updateContext);
    }
    
    /**
     * Alter Subscription and product table.
     */
    private function alterSubscriptionTable(): void
    {
       /* @var Connection $connection */
        $connection = $this->container->get(Connection::class);
        
        // implement update
        $isTableExists  = $connection->executeQuery('
            SELECT COUNT(*) as exists_tbl
            FROM information_schema.tables
            WHERE table_name IN ("novalnet_product_config")
            AND table_schema = database()
        ')->fetch();
        
        $isNovalnetSubscriptionTableExists  = $connection->executeQuery('
            SELECT COUNT(*) as exists_tbl
            FROM information_schema.tables
            WHERE table_name IN ("novalnet_subscription")
            AND table_schema = database()
        ')->fetch();
        
        if (empty($isNovalnetSubscriptionTableExists['exists_tbl']))
        {
			return;
		}
        
        if (!empty($isTableExists['exists_tbl'])) {
            
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "multiple_subscription"'))
            {
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `multiple_subscription` TINYINT(2) DEFAULT 0 AFTER `free_trial_period`;');
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `multi_subscription_options` JSON DEFAULT NULL AFTER `multiple_subscription`;');
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `operational_month` INT(11) DEFAULT 0 AFTER `multi_subscription_options`;');
            }
            
            if ($connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "inherit"')) 
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` DROP COLUMN `inherit`;');
            
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "discount"'))
				$connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `discount` double DEFAULT NULL AFTER `operational_month`;');
				
			if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "predefined_selection"'))
				$connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `predefined_selection` VARCHAR(50) DEFAULT NULL;');
            
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_subscription` LIKE "discount"'))
            {
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `discount` double DEFAULT NULL AFTER `amount`;');    
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `date_change_reason` TEXT DEFAULT NULL AFTER `cancel_reason`;');
            }
            
            if ($connection->fetchOne('SHOW COLUMNS FROM `novalnet_subscription` LIKE "customer_cancel_option"'))
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` DROP COLUMN `customer_cancel_option`;');
            
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_subscription` LIKE "quantity"'))
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `quantity` INT(3) DEFAULT NULL AFTER `line_item_id`;');
            
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_subscription` LIKE "product_id"'))
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `product_id` binary(16) DEFAULT NULL AFTER `line_item_id`;');
                
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_subscription` LIKE "last_day_month"'))
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `last_day_month` TINYINT(2) DEFAULT "0" AFTER `next_date`;');
            
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "discount_scope"'))
            {
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `discount_scope` VARCHAR(50) DEFAULT NULL AFTER `discount`;');
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `discount_type` VARCHAR(50) DEFAULT NULL AFTER `discount_scope`;');
			}
			
			if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "display_name"'))
            {
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `display_name` VARCHAR(255) DEFAULT NULL AFTER `type`;');
			}
			
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_product_config` LIKE "discount_details"'))
            {
                $connection->executeStatement('ALTER TABLE `novalnet_product_config` ADD `discount_details` LONGTEXT DEFAULT NULL AFTER `discount_scope`;');
			}
			
                
            if (!$connection->fetchOne('SHOW COLUMNS FROM `novalnet_subscription` LIKE "discount_scope"'))
            {
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `discount_scope` VARCHAR(50) DEFAULT NULL AFTER `discount`;');
                $connection->executeStatement('ALTER TABLE `novalnet_subscription` ADD `discount_type` VARCHAR(50) DEFAULT NULL AFTER `discount_scope`;');
			}
			
			$connection->executeStatement('ALTER TABLE `novalnet_subscription` MODIFY `discount` double DEFAULT NULL;');
			
        }
    }
}
