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

namespace Novalnet\NovalnetSubscription\Helper;

use Novalnet\NovalnetSubscription\NovalnetSubscription;

class CartHelper
{
    public const INTERVAL_MAP = [
        NovalnetSubscription::INTERVAL_TYPE_DAILY => 'days',
        NovalnetSubscription::INTERVAL_TYPE_WEEKLY => 'weeks',
        NovalnetSubscription::INTERVAL_TYPE_WEEKS_2 => 'weeks',
        NovalnetSubscription::INTERVAL_TYPE_WEEKS_3 => 'weeks',
        NovalnetSubscription::INTERVAL_TYPE_WEEKS_6 => 'weeks',
        NovalnetSubscription::INTERVAL_TYPE_MONTHLY => 'months',
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_2 => 'months',
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_3 => 'months',
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_4 => 'months',
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_9 => 'months',
        NovalnetSubscription::INTERVAL_TYPE_HALF_YEARLY => 'months',
        NovalnetSubscription::INTERVAL_TYPE_YEARLY => 'years'
    ];
    
    public const INTERVAL_COUNT_MAP = [
        NovalnetSubscription::INTERVAL_TYPE_WEEKS_2 => 2,
        NovalnetSubscription::INTERVAL_TYPE_WEEKS_3 => 3,
        NovalnetSubscription::INTERVAL_TYPE_WEEKS_6 => 6,
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_2 => 2,
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_3 => 3,
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_4 => 4,
        NovalnetSubscription::INTERVAL_TYPE_HALF_YEARLY => 6,
        NovalnetSubscription::INTERVAL_TYPE_MONTHS_9 => 9
    ];
    
    /**
     * Return the interval period
     *
     * @param string $intervalType
     *
     * @return string|null
     */
    public static function getIntervalPeriod(string $intervalType): ?string
    {
        return !empty(self::INTERVAL_MAP[$intervalType]) ? self::INTERVAL_MAP[$intervalType] : null;
    }
    
    /**
     * Return the interval count
     *
     * @param string $intervalType
     *
     * @return int
     */
    public static function getIntervalType(string $intervalType): int
    {
        return !empty(self::INTERVAL_COUNT_MAP[$intervalType]) ? self::INTERVAL_COUNT_MAP[$intervalType] : 1;
    }
}
