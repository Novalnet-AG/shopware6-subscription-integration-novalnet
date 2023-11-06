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

namespace Novalnet\NovalnetSubscription\Components;

use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubsCycleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 * NovalnetSubsCycleRepository Class.
 */
class NovalnetSubsCycleRepository
{
    
    /**
     * @var EntityRepository
     */
    private $subsCycleRepository;

    /**
     * Constructs a `NovalnetSubsCycleRepository`
     *
     * @param EntityRepository $subsCycleRepository
     */
    public function __construct(EntityRepository $subsCycleRepository)
    {
        $this->subsCycleRepository      = $subsCycleRepository;
    }
    
    /**
     * Insert/update the novalnet subscription cycles
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array $data
     */
    public function insertSubscriptionCycles(SalesChannelContext $salesChannelContext, array $data): void
    {
        if (!is_null($salesChannelContext->getCustomer())) {
            $data ['id'] = Uuid::randomHex();
            $this->subsCycleRepository->upsert([$data], $salesChannelContext->getContext());
        }
    }
    
    /**
     * Update the novalnet subscription cycles
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array $data
     */
    public function updateCycles(SalesChannelContext $salesChannelContext, array $data): void
    {
        if (!is_null($salesChannelContext->getCustomer())) {
            $this->subsCycleRepository->upsert([$data], $salesChannelContext->getContext());
        }
    }
    
    /**
     * Get existing subscription cycle data
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array $data
     *
     * @return NovalnetSubsCycleEntity|null
     */
    public function getExistingCycles(SalesChannelContext $salesChannelContext, array $data): ?NovalnetSubsCycleEntity
    {
        $result = null;
        if (!is_null($salesChannelContext->getCustomer())) {
            $criteria = new Criteria();
            
            if (! empty($data['orderId'])) {
				$criteria->addFilter(
					new EqualsFilter('novalnet_subs_cycle.orderId', $data['orderId'])
				);
			}

            if (! empty($data['subsId'])) {
                $criteria->addFilter(
                    new EqualsFilter('novalnet_subs_cycle.subsId', $data['subsId'])
                );
            }
            $result = $this->subsCycleRepository->search($criteria, $salesChannelContext->getContext())->first();
        }
        return $result;
    }
}
