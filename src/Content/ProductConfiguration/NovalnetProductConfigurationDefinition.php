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

namespace Novalnet\NovalnetSubscription\Content\ProductConfiguration;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class NovalnetProductConfigurationDefinition extends EntityDefinition
{
    public function getEntityName(): string
    {
        return 'novalnet_product_config';
    }

    public function getCollectionClass(): string
    {
        return NovalnetProductConfigurationCollection::class;
    }

    public function getEntityClass(): string
    {
        return NovalnetProductConfigurationEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->setFlags(new PrimaryKey(), new Required()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->setFlags(new Required()),
            (new BoolField('active', 'active')),
            (new StringField('type', 'type')),
            (new StringField('display_name', 'displayName')),
            (new IntField('interval', 'interval')),
            (new StringField('period', 'period')),
            (new IntField('subscription_length', 'subscriptionLength')),
            (new FloatField('sign_up_fee', 'signUpFee')),
            (new IntField('free_trial', 'freeTrial')),
            (new StringField('free_trial_period', 'freeTrialPeriod')),
            (new BoolField('multiple_subscription', 'multipleSubscription')),
            (new JsonField('multi_subscription_options', 'multiSubscriptionOptions')),
            (new IntField('operational_month', 'operationalMonth')),
            (new FloatField('discount', 'discount')),
            (new StringField('discount_scope', 'discountScope')),
            (new StringField('discount_type', 'discountType')),
            (new LongTextField('detail_page_text', 'detailPageText'))->setFlags(),
            (new StringField('predefined_selection', 'predefinedSelection')),
            (new LongTextField('discount_details', 'discountDetails')),
            (new OneToOneAssociationField('product', 'product_id', 'id', ProductDefinition::class, false))
        ]);
    }
}
