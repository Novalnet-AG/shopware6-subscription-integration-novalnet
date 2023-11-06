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

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemDefinition;
use Shopware\Core\Checkout\Payment\PaymentMethodDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;

class NovalnetSubscriptionDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'novalnet_subscription';
    }

    public function getCollectionClass(): string
    {
        return NovalnetSubscriptionCollection::class;
    }

    public function getEntityClass(): string
    {
        return NovalnetSubscriptionEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new NumberRangeField('subs_number', 'subsNumber'))->addFlags(new SearchRanking(SearchRanking::HIGH_SEARCH_RANKING)),
            (new FkField('order_id', 'orderId', OrderDefinition::class))->addFlags(new Required()),
            (new FkField('line_item_id', 'lineItemId', OrderLineItemDefinition::class))->addFlags(new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class)),
            (new IntField('quantity', 'quantity')),
            (new FkField('customer_id', 'customerId', CustomerDefinition::class))->addFlags(new Required()),
            (new FkField('payment_method_id', 'paymentMethodId', PaymentMethodDefinition::class))->addFlags(new Required()),
            (new StringField('payment_reference', 'paymentReference')),
            (new IntField('interval', 'interval')),
            (new StringField('unit', 'unit')),
            (new IntField('length', 'length')),
            (new FloatField('amount', 'amount')),
            (new FloatField('discount', 'discount')),
            (new StringField('discount_scope', 'discountScope')),
            (new StringField('discount_type', 'discountType')),
            (new StringField('status', 'status'))->addFlags(new Required()),
            (new DateTimeField('next_date', 'nextDate')),
            (new BoolField('last_day_month', 'lastDayMonth')),
            (new DateTimeField('ending_at', 'endingAt')),
            (new IntField('trial_interval', 'trialInterval')),
            (new StringField('trial_unit', 'trialUnit')),
            (new DateTimeField('cancelled_at', 'cancelledAt')),
            (new StringField('cancel_reason', 'cancelReason')),
            (new StringField('date_change_reason', 'dateChangeReason')),
            (new DateTimeField('termination_date', 'terminationDate')),
            (new FkField('canceled_by', 'canceledBy', CustomerDefinition::class))->addFlags(),
            (new BoolField('shipping_calculate_once', 'shippingCalculateOnce')),
            
            (new OneToOneAssociationField('order', 'order_id', 'id', OrderDefinition::class, false)),
            (new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, false)),
            (new OneToManyAssociationField('subsOrders', NovalnetSubsCycleDefinition::class, 'subs_id'))->addFlags(
                new SearchRanking(SearchRanking::ASSOCIATION_SEARCH_RANKING)
            ),
            (new ManyToOneAssociationField('customer', 'customer_id', CustomerDefinition::class, 'id', false)),
            (new ManyToOneAssociationField('payment_method', 'payment_method_id', PaymentMethodDefinition::class, 'id', false)),
        ]);
    }
}
