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

namespace Novalnet\NovalnetSubscription\Subscriber\Product;

use Novalnet\NovalnetSubscription\Content\ProductConfiguration\NovalnetProductConfigurationEntity;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductSearchCriteriaEvent;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageLoadedSubscriber implements EventSubscriberInterface
{

    /**
     * Register subscribed events
     *
     * return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ProductSearchCriteriaEvent::class => 'novalnetConfiguration',
            ProductListingCriteriaEvent::class => 'novalnetConfiguration'
        ];
    }

    public function novalnetConfiguration(NestedEvent $event)
    {
        if (!$event instanceof ProductSearchCriteriaEvent
            && !$event instanceof ProductListingCriteriaEvent
        ) {
            return;
        }
        
        $event->getCriteria()->addAssociation('novalnetConfiguration');
    }
}
