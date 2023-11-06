<?php declare(strict_types=1);

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
 
namespace Novalnet\NovalnetSubscription\Content\Order\Extension;

use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubscriptionDefinition;
use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubsCycleDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class OrderAboReferenceExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField('subsOrders', 'id', 'order_id', NovalnetSubsCycleDefinition::class, false))
                ->addFlags()
        );
        
        $collection->add(
            (new OneToManyAssociationField('novalnetSubscription', NovalnetSubscriptionDefinition::class, 'order_id', 'id'))
                ->addFlags()
        );
    }

    public function getDefinitionClass(): string
    {
        return OrderDefinition::class;
    }
}
