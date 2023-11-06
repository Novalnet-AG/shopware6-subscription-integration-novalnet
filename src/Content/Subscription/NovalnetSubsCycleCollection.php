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

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                         add(NovalnetSubsCycleEntity $entity)
 * @method void                         set(string $key, NovalnetSubsCycleEntity $entity)
 * @method NovalnetSubsCycleEntity[]    getIterator()
 * @method NovalnetSubsCycleEntity[]    getElements()
 * @method null|NovalnetSubsCycleEntity get(string $key)
 * @method null|NovalnetSubsCycleEntity first()
 * @method null|NovalnetSubsCycleEntity last()
 */
class NovalnetSubsCycleCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return NovalnetSubsCycleEntity::class;
    }
}
