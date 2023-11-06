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

use Novalnet\NovalnetSubscription\Helper\Helper;
use Novalnet\NovalnetSubscription\Content\ProductConfiguration\NovalnetProductConfigurationEntity;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\AbsolutePriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Rule\LineItemRule;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\Translation\TranslatorInterface;

class CartProcessor implements CartProcessorInterface
{	
    /**
     * @var AbsolutePriceCalculator
     */
    private $calculator;
    
    /**
     * @var EntityRepository
     */
    private $productRepository;
    
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        AbsolutePriceCalculator $calculator,
        EntityRepository $productRepository,
        TranslatorInterface $translator,
        Helper $helper
    ) {
        $this->calculator = $calculator;
        $this->translator = $translator;
        $this->helper     = $helper;
        $this->productRepository = $productRepository;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        // get all order line items
        $orderLineItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        // no line item found? early return
        if ($orderLineItems->count() === 0) {
            return;
        }
         // set the shop locale to display the message
        $localeCode = $this->helper->getLocaleFromOrder($context->getcontext()->getLanguageId());
        
        $freeTrialAmount = $signUpFeeAmount = $discountAmount = 0;
        $freeTrialkeys = $signUpFeekeys = $discountkeys = [];

        $novalnetSettings = $this->helper->getSubscriptionSettings($context->getSalesChannel()->getId());
        
        if ($original->getExtension('isRecurring') !== null) {
            $orderLineItems = $original->getLineItems();
            
            foreach ($orderLineItems as $id => $orderLineItem) {
                if ($orderLineItem->getType() == 'product') {
                    if ($original->getExtension('discount') !== null) {
                        $discountAvailable = $original->getExtension('discount')->all()['discount'];
                        if (!empty($discountAvailable)) {
                            $discountAmount += (!empty($original->getExtension('discountType')->all()['discountType']) && $original->getExtension('discountType')->all()['discountType'] == 'fixed') ? ($discountAvailable * (int) $orderLineItem->getQuantity()) : ($orderLineItem->getPrice()->getTotalPrice() / 100) * $discountAvailable;
                            array_push($discountkeys, $orderLineItem->getReferencedId());
                        }
                    }
                }
            }
        } else {
            foreach ($orderLineItems as $lineItem) {
                if ($this->helper->getSession('novalnetConfiguration') && !$original->hasExtension('novalnetConfiguration')) {
                    $original->addExtension(
                        'novalnetConfiguration',
                        new ArrayStruct(
                            $this->helper->getSession('novalnetConfiguration')
                        )
                    );
                }
                
                if (!$original->hasExtension('novalnetConfiguration')) {
                    return;
                }
                
                $pageExtension = $original->getExtension('novalnetConfiguration');
                if (!$pageExtension instanceof ArrayStruct) {
                    return;
                }
                
                foreach ($original->getExtension('novalnetConfiguration')->all() as $id => $cartLineItem) {
                    $formattedLineItemId = str_replace('_', '', $id);
                    if ($formattedLineItemId === $lineItem->getId()) {
                        /** @var string $productId */
                        $productId = $lineItem->getReferencedId();
                        $criteria = new Criteria([$productId]);
                        $criteria->addAssociation('novalnetConfiguration');

                        $product = $this->productRepository->search($criteria, $context->getContext())->first();
                        
                        if (!$product instanceof ProductEntity) {
                            continue;
                        }
                        
                        // get for parent product
                        if (!$product->hasExtension('novalnetConfiguration') && $product->getParentId())
                        {
							$criteria = new Criteria([$product->getParentId()]);
							$criteria->addAssociation('novalnetConfiguration');

							$product = $this->productRepository->search($criteria, $context->getContext())->first();
						}
                        
                        if ($product->hasExtension('novalnetConfiguration')) {
                            $novalnetExtension = $product->getExtension('novalnetConfiguration');
                            
                            if (!$novalnetExtension instanceof NovalnetProductConfigurationEntity
                            || $novalnetExtension->getType() === 'no_abo' || empty($novalnetExtension->getActive())
                            ) {
                                continue;
                            }
                            
                            if ($novalnetExtension->getFreeTrial()) {
                                $freeTrialAmount += $lineItem->getPrice()->getTotalPrice();
                                array_push($freeTrialkeys, $lineItem->getReferencedId());
                            }
                            
                            if ($novalnetExtension->getSignUpFee()) {
                                $signUpFeeAmount += $novalnetExtension->getSignUpFee() * $lineItem->getQuantity();
                                array_push($signUpFeekeys, $lineItem->getReferencedId());
                            }
                        
                            if(!empty($novalnetExtension->getDiscount()) && empty($novalnetExtension->getFreeTrial()) && !in_array($novalnetExtension->getDiscountScope(),['last','cycleduration'])) {
								
                                if (!empty($novalnetExtension->getDiscountType()) && !empty($novalnetExtension->getDiscountScope()))
                                {
									if ($novalnetExtension->getDiscountType() == 'percentage' && $novalnetExtension->getDiscount() <= 100)
									{
										$discountAmount += ($lineItem->getPrice()->getTotalPrice() / 100) * $novalnetExtension->getDiscount();
									} elseif ($novalnetExtension->getDiscountType() == 'fixed' && $novalnetExtension->getDiscount() <= $lineItem->getPrice()->getUnitPrice()) 
									{
										$discountAmount += $novalnetExtension->getDiscount() * (int) $lineItem->getQuantity();
									}
								} else {
									$discountAmount += ($lineItem->getPrice()->getTotalPrice() / 100) * $novalnetExtension->getDiscount();
								}
                                array_push($discountkeys, $lineItem->getReferencedId());
                            } 
                            elseif (empty($novalnetExtension->getFreeTrial()) && $novalnetExtension->getDiscountScope() == 'cycleduration' && !empty($novalnetExtension->getDiscountDetails())) {
								
									$discountDetails = json_decode($novalnetExtension->getDiscountDetails(), true);
									$productDiscount = [];
									
									foreach($discountDetails as $period=>$periodDiscount){
										if($period == $cartLineItem['delivery']){
											$productDiscount = $periodDiscount;
										}
									}
									
									if(!empty($productDiscount)){
										if((!empty($productDiscount['type']) && ($productDiscount['type']== 'percentage')) && (!empty($productDiscount['discount']) && $productDiscount['discount'] <= 100))
										{
											$discountAmount += ($lineItem->getPrice()->getTotalPrice() / 100) * $productDiscount['discount'];
										} elseif ((!empty($productDiscount['type']) && ($productDiscount['type'] == 'fixed')) && (!empty($productDiscount['discount']) && ($productDiscount['discount'] <= $lineItem->getPrice()->getUnitPrice())))
										{
											$discountAmount += $productDiscount['discount'] * (int) $lineItem->getQuantity();
										}
									}
                                 array_push($discountkeys, $lineItem->getReferencedId());
                            } 
                        }
                    }
                }
            }
        }
        
        if ($freeTrialAmount > 0) {
            $freeTrialLineItem = $this->createField(Uuid::randomHex(), $this->translator->trans('NovalnetSubscription.text.freeTrial', [], null, $localeCode), 'free_trial');
            
            // declare price definition to define how this price is calculated
            $definition = new AbsolutePriceDefinition(
                -$freeTrialAmount,
                new LineItemRule(LineItemRule::OPERATOR_EQ, $freeTrialkeys)
            );

            $freeTrialLineItem->setPriceDefinition($definition);

            // calculate price
            $freeTrialLineItem->setPrice(
                $this->calculator->calculate($definition->getPrice(), $orderLineItems->getPrices(), $context)
            );

            if ($this->helper->getSession('novalnetConfiguration')) {
                    $freeTrialLineItem->addExtension(
                        'novalnetConfiguration',
                        new ArrayStruct(
                            $this->helper->getSession('novalnetConfiguration')
                        )
                    );
            }
            // add discount to new cart
            $toCalculate->add($freeTrialLineItem);
        }
        
        if ($discountAmount > 0) {
            $discountLineItem = $this->createField(Uuid::randomHex(), $this->translator->trans('NovalnetSubscription.text.discount', [], null, $localeCode), 'nn_discount');
            
            // declare price definition to define how this price is calculated
			$definition = new AbsolutePriceDefinition(
				-$discountAmount,
				new LineItemRule(LineItemRule::OPERATOR_EQ, $discountkeys)
			);
            
            $discountLineItem->setPriceDefinition($definition);

            // calculate price
            $discountLineItem->setPrice(
                $this->calculator->calculate($definition->getPrice(), $orderLineItems->getPrices(), $context)
            );

            if ($this->helper->getSession('novalnetConfiguration')) {
                    $discountLineItem->addExtension(
                        'novalnetConfiguration',
                        new ArrayStruct(
                            $this->helper->getSession('novalnetConfiguration')
                        )
                    );
            }
            // add discount to new cart
            $toCalculate->add($discountLineItem);
        }
        
        if ($signUpFeeAmount > 0) {
            $signupLineItem = $this->createField(Uuid::randomHex(), $this->translator->trans('NovalnetSubscription.text.initialSetupFee', [], null, $localeCode), 'signup_fee');
            
            $convertedAmount = $context->getCurrency()->getFactor() * $signUpFeeAmount;
            
            // declare price definition to define how this price is calculated
            $definition = new AbsolutePriceDefinition(
                $convertedAmount,
                new LineItemRule(LineItemRule::OPERATOR_EQ, $signUpFeekeys)
            );

            $signupLineItem->setPriceDefinition($definition);

            // calculate price
            $signupLineItem->setPrice(
                $this->calculator->calculate($definition->getPrice(), $orderLineItems->getPrices(), $context)
            );
            if ($this->helper->getSession('novalnetConfiguration')) {
                    $signupLineItem->addExtension(
                        'novalnetConfiguration',
                        new ArrayStruct(
                            $this->helper->getSession('novalnetConfiguration')
                        )
                    );
            }

            // add discount to new cart
            $toCalculate->add($signupLineItem);
        }
    }
    
    private function createField(string $id, string $label, string $type): LineItem
    {
        $freeTrialLineItem = new LineItem($id, $type, $id, 1);

        $freeTrialLineItem->setLabel($label);
        $freeTrialLineItem->setGood(false);
        $freeTrialLineItem->setStackable(true);
        $freeTrialLineItem->setRemovable(false);

        return $freeTrialLineItem;
    }
}
