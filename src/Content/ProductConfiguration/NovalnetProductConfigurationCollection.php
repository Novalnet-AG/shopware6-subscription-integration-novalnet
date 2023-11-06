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

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(NovalnetProductConfigurationEntity $entity)
 * @method void                         set(string $key, NovalnetProductConfigurationEntity $entity)
 * @method NovalnetProductConfigurationEntity[]    getIterator()
 * @method NovalnetProductConfigurationEntity[]    getElements()
 * @method null|NovalnetProductConfigurationEntity get(string $key)
 * @method null|NovalnetProductConfigurationEntity first()
 * @method null|NovalnetProductConfigurationEntity last()
 */
class NovalnetProductConfigurationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NovalnetProductConfigurationEntity::class;
    }
}
