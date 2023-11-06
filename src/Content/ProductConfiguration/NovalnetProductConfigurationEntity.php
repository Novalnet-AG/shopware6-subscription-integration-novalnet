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

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class NovalnetProductConfigurationEntity extends Entity
{
    use EntityIdTrait;
    
    /**
     * @var string
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $productId;
    
    /**
     * @var bool|null
     */
    protected $active;
    
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var string|null
     */
    protected $displayName;
    
    /**
     * @var int|null
     */
    protected $interval;
    
    /**
     * @var string
     */
    protected $period;
    
    /**
     * @var int
     */
    protected $subscriptionLength;
    
    /**
     * @var float
     */
    protected $signUpFee;
    
    /**
     * @var int
     */
    protected $freeTrial;
    
    /**
     * @var string
     */
    protected $freeTrialPeriod;
    
    /**
     * @var bool|null
     */
    protected $multipleSubscription;
    
    /**
     * @var array|null
     */
    protected $multiSubscriptionOptions;
    
    /**
     * @var int
     */
    protected $operationalMonth;
    
    /**
     * @var float|null
     */
    protected $discount;
    
    /**
     * @var string|null
     */
    protected $discountScope;
    
    /**
     * @var string|null
     */
    protected $discountType;
    
    /**
     * @var string
     */
    protected $detailPageText;
    
    /**
     * @var string|null
     */
    protected $predefinedSelection;
    
    /**
     * @var string|null
     */
    protected $discountDetails;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
    
    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }
    
    public function getActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
    
    public function getDisplayName(): ?string
    {
        return (string) $this->displayName;
    }

    public function setDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }
    
    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(?int $interval): void
    {
        $this->interval = $interval;
    }
    
    public function getPeriod(): ?string
    {
        return (string) $this->period;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }
    
    public function getSubscriptionLength(): ?int
    {
        return (int) $this->subscriptionLength;
    }

    public function setSubscriptionLength(int $subscriptionLength): void
    {
        $this->subscriptionLength = $subscriptionLength;
    }
    
    public function getSignUpFee(): ?float
    {
        return $this->signUpFee;
    }

    public function setSignUpFee(float $signUpFee): void
    {
        $this->signUpFee = $signUpFee;
    }
    
    public function getFreeTrial(): ?int
    {
        return $this->freeTrial;
    }

    public function setFreeTrial(int $freeTrial): void
    {
        $this->freeTrial = $freeTrial;
    }
    
    public function getFreeTrialPeriod(): ?string
    {
        return $this->freeTrialPeriod;
    }

    public function setFreeTrialPeriod(string $freeTrialPeriod): void
    {
        $this->freeTrialPeriod = $freeTrialPeriod;
    }
    
    public function getMultipleSubscription(): ?bool
    {
        return $this->multipleSubscription;
    }

    public function setMultipleSubscription(bool $multipleSubscription): void
    {
        $this->multipleSubscription = $multipleSubscription;
    }
    
    public function getMultiSubscriptionOptions(): ?array
    {
        return $this->multiSubscriptionOptions;
    }

    public function setMultiSubscriptionOptions(array $multiSubscriptionOptions): void
    {
        $this->multiSubscriptionOptions = $multiSubscriptionOptions;
    }
    
    public function getOperationalMonth(): ?int
    {
        return $this->operationalMonth;
    }

    public function setOperationalMonth(int $operationalMonth): void
    {
        $this->operationalMonth = $operationalMonth;
    }
    
    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(float $discount): void
    {
        $this->discount = $discount;
    }
    
    public function getDiscountScope(): ?string
    {
        return $this->discountScope;
    }

    public function setDiscountScope(string $discountScope): void
    {
        $this->discountScope = $discountScope;
    }
    
    public function getDiscountType(): ?string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): void
    {
        $this->discountType = $discountType;
    }
    
    public function getDetailPageText(): ?string
    {
        return $this->detailPageText;
    }

    public function setDetailPageText(string $detailPageText): void
    {
        $this->detailPageText = $detailPageText;
    }
    
    public function getPredefinedSelection(): ?string
    {
        return $this->predefinedSelection;
    }

    public function setPredefinedSelection(string $predefinedSelection): void
    {
        $this->predefinedSelection = $predefinedSelection;
    }
    
    public function getDiscountDetails(): ?string
    {
        return $this->discountDetails;
    }

    public function setDiscountDetails(string $discountDetails): void
    {
        $this->discountDetails = $discountDetails;
    }
}
