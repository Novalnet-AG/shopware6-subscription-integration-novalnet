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

use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Novalnet\NovalnetSubscription\Content\Subscription\NovalnetSubscriptionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;

/**
 * NovalnetSubscriptionRepository Class.
 */
class NovalnetSubscriptionRepository
{
    
    /**
     * @var EntityRepository
     */
    private $subscriptionRepository;
    
    /**
     * @var NumberRangeValueGeneratorInterface
     */
    private $numberRangeValueGenerator;

    /**
     * Constructs a `NovalnetSubscriptionRepository`
     *
     * @param EntityRepository $subscriptionRepository
     */
    public function __construct(EntityRepository $subscriptionRepository, NumberRangeValueGeneratorInterface $numberRangeValueGenerator)
    {
        $this->subscriptionRepository       = $subscriptionRepository;
        $this->numberRangeValueGenerator    = $numberRangeValueGenerator;
    }
    
    /**
     * Insert/update the novalnet subscription orders
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array $data
     *
     * @return string|null
     */
    public function saveSubscription(SalesChannelContext $salesChannelContext, array $data): ?string
    {
        if (!empty($salesChannelContext->getCustomer())) {
            $subscriptionData  = $this->getExistingOrder($salesChannelContext, $data);
            
            if (empty($subscriptionData)) {
                $data ['id'] = Uuid::randomHex();
                if (empty($data['subsNumber'])) {
                    $data['subsNumber'] = $this->numberRangeValueGenerator->getValue(
                        NovalnetSubscription::SUBSCRIPTION_NUMBER_RANGE_TECHNICAL_NAME,
                        $salesChannelContext->getContext(),
                        $salesChannelContext->getSalesChannel()->getId()
                    );
                }
            } else {
                $data ['id'] = $subscriptionData->getId();
            }

            $this->subscriptionRepository->upsert([$data], $salesChannelContext->getContext());
            
            return $data ['id'];
        }
        
        return null;
    }
    
    /**
     * Get existing subscription order data
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array $data
     *
     * @return NovalnetSubscriptionEntity|null
     */
    public function getExistingOrder(SalesChannelContext $salesChannelContext, array $data): ?NovalnetSubscriptionEntity
    {
        $result = null;
        if (!empty($salesChannelContext->getCustomer())) {
            $criteria = new Criteria();

            $criteria->addFilter(
                new EqualsFilter('novalnet_subscription.customerId', $salesChannelContext->getCustomer()->getId())
            );
            
            $criteria->addFilter(
                new EqualsFilter('novalnet_subscription.orderId', $data['orderId'])
            );

            if (! empty($data['lineItemId'])) {
                $criteria->addFilter(
                    new EqualsFilter('novalnet_subscription.lineItemId', $data['lineItemId'])
                );
            }

            $result = $this->subscriptionRepository->search($criteria, $salesChannelContext->getContext())->first();
        }
        return $result;
    }
    
    /**
     * update the novalnet subscription orders
     *
     * @param SalesChannelContext $salesChannelContext
     * @param array $data
     *
     * @return void
     */
    public function updateSubscription(SalesChannelContext $salesChannelContext, array $data): void
    {
        $this->subscriptionRepository->upsert([$data], $salesChannelContext->getContext());
    }
    
    /**
     * Get existing subscription order data
     *
     * @param SalesChannelContext $salesChannelContext
     * @param string $data
     *
     * @return NovalnetSubscriptionEntity|null
     */
    public function getSubOrder(SalesChannelContext $salesChannelContext, string  $data): ?NovalnetSubscriptionEntity
    {
        $result = null;
        if ($data) {
            $criteria = new Criteria();
            $criteria->addAssociation('subsOrders');
            $criteria->addFilter(
                new EqualsFilter('novalnet_subscription.id', $data)
            );
       
            return $this->subscriptionRepository->search($criteria, $salesChannelContext->getContext())->first();
        }
        
        return $result;
    }
}
