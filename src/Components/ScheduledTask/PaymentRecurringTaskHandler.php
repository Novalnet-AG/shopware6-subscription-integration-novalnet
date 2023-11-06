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

namespace Novalnet\NovalnetSubscription\Components\ScheduledTask;

use Novalnet\NovalnetSubscription\Components\PaymentRecurringService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Shopware\Core\Defaults;

/**
 * @internal
 */

class PaymentRecurringTaskHandler extends ScheduledTaskHandler
{
    
    /**
     * @var PaymentRecurringService
     */
    private $paymentRecurringService;

    public function __construct(
        EntityRepository $scheduledTaskRepository,
        PaymentRecurringService $paymentRecurringService
    ) {
        parent::__construct($scheduledTaskRepository);

        $this->paymentRecurringService = $paymentRecurringService;
    }
    
    /**
     * @return class-string[]
     */
     
    public static function getHandledMessages(): iterable
    {
        return [PaymentRecurringTask::class];
    }

    public function run(): void
    {
        $this->paymentRecurringService->run();
    }
}
