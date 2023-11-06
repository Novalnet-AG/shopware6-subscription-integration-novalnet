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

namespace Novalnet\NovalnetSubscription\Subscriber\Order;

use Novalnet\NovalnetSubscription\NovalnetSubscription;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Checkout\Order\OrderStates;
use Novalnet\NovalnetSubscription\Components\NovalnetSubscriptionRepository;
use Novalnet\NovalnetSubscription\Components\NovalnetSubsCycleRepository;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * PaymentEventSubscriber Class.
 */
class orderStateEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepository
     */
    private $orderRepository;
    
    /**
     * @var EntityRepository
     */
    private $stateMachineStateRepository;
    /**
     * @var EntityRepository
     */
    private $orderTransactionRepository;
    /**
     * @var EntityRepository
     */
    private $novalnetSubscriptionRepository;
    
    /**
     * @var EntityRepository
     */
    private $novalnetSubcycleRepository;
    
    /**
     * Constructs a `PaymentEventSubscriber`
     *

     * @param RequestStack $requestStack
     */
    public function __construct(
        EntityRepository $orderRepository,
        EntityRepository $stateMachineStateRepository,
        EntityRepository $novalnetSubscriptionRepository,
        EntityRepository $novalnetSubcycleRepository,
        EntityRepository $orderTransactionRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->stateMachineStateRepository = $stateMachineStateRepository;
        $this->novalnetSubscriptionRepository = $novalnetSubscriptionRepository;
        $this->novalnetSubcycleRepository = $novalnetSubcycleRepository;
        $this->orderTransactionRepository = $orderTransactionRepository;
    }
    
    /**
     * Get subscribed events
     *
     * return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_TRANSACTION_WRITTEN_EVENT   => 'orderStateLoaded'
        ];
    }
    
    /**
     *
     * @params EntityLoadedEvent $event
     */
    public function orderStateLoaded(EntityWrittenEvent $event): void
    {
        //~ $payload = $event->getPayloads();
        //~ $criteria = new Criteria();
        //~ $criteria->addFilter(new EqualsFilter('id', $payload[0]['id']));
        //~ $data = $this->orderTransactionRepository->search($criteria, $event->getContext());
        //~ if ($data) {
            //~ $currentOrderState = $data->first();
            //~ $subCriteria = new Criteria();
            //~ $subCriteria->addFilter(new EqualsFilter('orderId', $currentOrderState->getOrderId()));
            //~ $novalnetSubcription = $this->novalnetSubscriptionRepository->search($subCriteria, $event->getContext());
            //~ $subscription = $novalnetSubcription->first();
            
            //~ $subcycleCriteria = new Criteria();
            //~ $subcycleCriteria->addFilter(new EqualsFilter('orderId', $currentOrderState->getOrderId()));
            //~ $subCycle = $this->novalnetSubcycleRepository->search($subcycleCriteria, $event->getContext());
            //~ $subCycleValid = $subCycle->first();
            
            //~ if ($subscription != null) {
                //~ $cycle = (is_null($subscription->getTrialInterval()) ? 1 : 0);
            //~ } elseif ($subCycleValid !=null) {
                //~ $cycle = $subCycleValid->getCycles();
            //~ }
            
            //~ if (in_array($currentOrderState->getStateMachineState()->getTechnicalName(), ['cancelled' ,'failed'])) {
                //~ if ($subscription) {
                    //~ $update = [ 'id' => $subscription->getId(), 'status' => NovalnetSubscription::SUBSCRIPTION_STATUS_SUSPENDED, 'cancelReason' => 'Parent order getting failed' ];
                    //~ $this->novalnetSubscriptionRepository->upsert([$update], $event->getContext());
                //~ }
                //~ if ($subCycleValid) {
                    //~ $subCycleUpdate = [ 'id' => $subCycleValid->getId(), 'status' => NovalnetSubscription::CYCLE_STATUS_RETRY, 'cycles' => $cycle];
                    //~ $this->novalnetSubcycleRepository->upsert([$subCycleUpdate], $event->getContext());
                //~ }
            //~ } else if(in_array($currentOrderState->getStateMachineState()->getTechnicalName(), ['open', 'paid_partially', 'paid', 'authorized', 'in_progress', 'reminded'])) {
                //~ if(!empty($subscription) && !in_array($subscription->getStatus(), ['PENDING_CANCEL', 'EXPIRED'])){
					//~ if ($subscription) {
						//~ $update = [ 'id' => $subscription->getId(), 'status' => NovalnetSubscription::SUBSCRIPTION_STATUS_ACTIVE];
						//~ $this->novalnetSubscriptionRepository->upsert([$update], $event->getContext());
					//~ }
					//~ if ($subCycleValid) {
						//~ $subCycleUpdate = [ 'id' => $subCycleValid->getId(), 'status' => NovalnetSubscription::CYCLE_STATUS_SUCCESS, 'cycles' => $cycle];
						//~ $this->novalnetSubcycleRepository->upsert([$subCycleUpdate], $event->getContext());
					//~ }
				//~ }
            //~ }
        //~ }
    }
}
