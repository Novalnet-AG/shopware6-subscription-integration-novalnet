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
 
namespace Novalnet\NovalnetSubscription\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
use Shopware\Core\Checkout\Cart\Delivery\DeliveryBuilder;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class DeliveryProcessor implements CartProcessorInterface
{
    
    public const MANUAL_SHIPPING_COSTS = 'manualShippingCosts';

    public const SKIP_DELIVERY_PRICE_RECALCULATION = 'skipDeliveryPriceRecalculation';
    
    /**
     * @var DeliveryBuilder
     */
    protected $builder;

    /**
     * @var DeliveryCalculator
     */
    protected $deliveryCalculator;

    /**
     * @var EntityRepository
     */
    protected $shippingMethodRepository;
    
    public function __construct(
        DeliveryBuilder $builder,
        DeliveryCalculator $deliveryCalculator,
        EntityRepository $shippingMethodRepository
    ) {
        $this->builder = $builder;
        $this->deliveryCalculator = $deliveryCalculator;
        $this->shippingMethodRepository = $shippingMethodRepository;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $deliveries = $this->builder->build($toCalculate, $data, $context, $behavior);

        $delivery = $deliveries->first();
          
        if (!$original->hasExtension('isNovalnetRecurring')) {
            return;
        }
        
        if ($behavior->hasPermission(self::SKIP_DELIVERY_PRICE_RECALCULATION)) {
            $originalDeliveries = $original->getDeliveries();

            $originalDelivery = $originalDeliveries->first();

            if ($delivery !== null && $originalDelivery !== null) {
                $originalDelivery->setShippingMethod($delivery->getShippingMethod());

                //Keep old prices
                $delivery->setShippingCosts(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()));

                //Recalculate tax
                $originalDelivery->setShippingCosts(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()));
            }

            // New shipping method (if changed) but with old prices
            $toCalculate->setDeliveries($originalDeliveries);

            return;
        }

        $manualShippingCosts = $original->getExtension(self::MANUAL_SHIPPING_COSTS);
        if ($delivery !== null && $manualShippingCosts instanceof CalculatedPrice) {
            $delivery->setShippingCosts(new CalculatedPrice(0, 0, new CalculatedTaxCollection(), new TaxRuleCollection()));
        }

        $toCalculate->setDeliveries($deliveries);
    }
}
