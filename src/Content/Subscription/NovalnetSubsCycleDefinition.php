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

namespace Novalnet\NovalnetSubscription\Content\Subscription;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class NovalnetSubsCycleDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'novalnet_subs_cycle';
    }

    public function getCollectionClass(): string
    {
        return NovalnetSubsCycleCollection::class;
    }

    public function getEntityClass(): string
    {
        return NovalnetSubsCycleEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('subs_id', 'subsId', NovalnetSubscriptionDefinition::class))->addFlags(new Required()),
            (new FkField('order_id', 'orderId', OrderDefinition::class)),
            (new FloatField('amount', 'amount')),
            (new IntField('interval', 'interval')),
            (new StringField('period', 'period')),
            (new FkField('payment_method_id', 'paymentMethodId', PaymentMethodDefinition::class)),
            (new IntField('cycles', 'cycles')),
            (new DateTimeField('cycle_date', 'cycleDate')),
            (new StringField('status', 'status'))->addFlags(new Required()),
            
            (new OneToOneAssociationField('order', 'order_id', 'id', OrderDefinition::class, false)),
            (new ManyToOneAssociationField('novalnetSubscription', 'subs_id', NovalnetSubscriptionDefinition::class, 'id', false))->addFlags()
        ]);
    }
}
