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
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\Group\LineItemGroupBuilder;
use Shopware\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Novalnet\NovalnetSubscription\Exception\PromotionNotValidError;
use Novalnet\NovalnetSubscription\Exception\DiscountNotValidError;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Struct\ArrayStruct;

class PromotionProcessor implements CartProcessorInterface
{
    public const DATA_KEY = 'promotions';
    public const LINE_ITEM_TYPE = 'promotion';

    public const SKIP_PROMOTION = 'skipPromotion';

    /**
     * @var LineItemGroupBuilder
     */
    private $groupBuilder;
    
    /**
     * @var AbsolutePriceCalculator
     */
    private $calculator;

    public function __construct(LineItemGroupBuilder $groupBuilder, AbsolutePriceCalculator $calculator)
    {
        $this->groupBuilder = $groupBuilder;
        $this->calculator   = $calculator;
    }
    
    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {   
        $toCalculate->getData()->set(LineItemGroupBuilder::class, $this->groupBuilder);
        
        if (!$original->hasExtension('novalnetConfiguration')) {
            return;
        }
        
        // get all order line items
        $orderLineItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);
        $lineItemPromotions = $original->getLineItems()->filterType(self::LINE_ITEM_TYPE);
        
        $pageExtension = $original->getExtension('novalnetConfiguration');
        if (!$pageExtension instanceof ArrayStruct) {
            return;
        }
        
        /** @var LineItemCollection $discountLineItems */
        $discountLineItems = $data->get(self::DATA_KEY);
        $freeTrialAmount = $discountAmount = 0;
		
		if (!empty($original->getExtension('novalnetConfiguration')) && !empty($original->getExtension('novalnetConfiguration')->all()) && $original->getPrice()->getPositionPrice() < 0 && !empty($lineItemPromotions))
		{
			
			foreach ($lineItemPromotions as $promotion)
			{	
				$updatedPromotionAmount = abs($promotion->getPrice()->getTotalPrice()) - abs($original->getPrice()->getPositionPrice());
				$definition = new AbsolutePriceDefinition(
					-$updatedPromotionAmount,
					new LineItemRule(LineItemRule::OPERATOR_EQ, [$promotion->getId()])
				);
				
				// calculate price
				$promotion->setPrice(
					$this->calculator->calculate($definition->getPrice(), $orderLineItems->getPrices(), $context)
				);
				
				// change the amount of the promotion
				$toCalculate->add($promotion);
				
				$original->setPrice(new CartPrice(0, 0, 0, new CalculatedTaxCollection(), new TaxRuleCollection(), CartPrice::TAX_STATE_NET));
				
				$toCalculate->addErrors(
                    new DiscountNotValidError($promotion->getLabel() ?? $promotion->getId())
                );
			}
		}
		
        foreach ($orderLineItems as $lineItem) {
            foreach ($original->getExtension('novalnetConfiguration')->all() as $id => $novalnetExtension) {
                $novalnetExtension = (array) $novalnetExtension;
                $formattedLineItemId = str_replace('_', '', $id);
                if ($formattedLineItemId === $lineItem->getId()) {
                    if ($novalnetExtension['type'] === 'no_abo' || empty($novalnetExtension['active'])) {
                        continue;
                    }
                    
                    if (!empty($novalnetExtension['freeTrial'])) {
                        $freeTrialAmount += $lineItem->getPrice()->getTotalPrice();
                    }
                }
            }
        }
        
        if (!empty($discountLineItems) && $freeTrialAmount > 0) {
            
            foreach ($lineItemPromotions as $lineItemPromotion) {
                $toCalculate->addErrors(
                    new PromotionNotValidError($lineItemPromotion->getLabel() ?? $lineItemPromotion->getId())
                );
                return;
            }
        }
    }
}
