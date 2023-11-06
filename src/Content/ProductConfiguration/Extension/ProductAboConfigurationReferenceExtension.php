<?php
declare(strict_types=1);
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
namespace Novalnet\NovalnetSubscription\Content\ProductConfiguration\Extension;

use Novalnet\NovalnetSubscription\Content\ProductConfiguration\NovalnetProductConfigurationDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductAboConfigurationReferenceExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField('novalnetConfiguration', 'id', 'product_id', NovalnetProductConfigurationDefinition::class, false))
                ->addFlags(new CascadeDelete())
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
