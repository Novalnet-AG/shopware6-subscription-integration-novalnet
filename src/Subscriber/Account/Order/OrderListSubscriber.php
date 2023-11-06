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

namespace Novalnet\NovalnetSubscription\Subscriber\Account\Order;

use Shopware\Storefront\Event\RouteRequest\OrderRouteRequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderListSubscriber implements EventSubscriberInterface
{

    /**
     * Register subscribed events
     *
     * return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderRouteRequestEvent::class => 'addOrderExtension',
        ];
    }

    public function addOrderExtension(OrderRouteRequestEvent $event)
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('subsOrders');
        $criteria->addAssociation('novalnetSubscription');
    }
}
