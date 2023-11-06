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

use DateTimeInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class NovalnetSubscriptionEntity extends Entity
{
    use EntityIdTrait;
    
    /**
     * @var string
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $subsNumber;
    
    /**
     * @var string
     */
    protected $orderId;
    
    /**
     * @var string
     */
    protected $lineItemId;
    
    /**
     * @var string
     */
    protected $productId;
    
    /**
     * @var int|null
     */
    protected $quantity;
    
    /**
     * @var string
     */
    protected $customerId;
    
    /**
     * @var string
     */
    protected $paymentMethodId;
    
    /**
     * @var string|null
     */
    protected $paymentReference;
    
    /**
     * @var int|null
     */
    protected $interval;
    
    /**
     * @var string
     */
    protected $unit;
    
    /**
     * @var int
     */
    protected $length;
    
    /**
     * @var float
     */
    protected $amount;
    
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
    protected $status;
    
    /**
     * @var DateTimeInterface|null
     */
    protected $nextDate;
    
    /**
     * @var bool|null
     */
    protected $lastDayMonth;
    
    /**
     * @var DateTimeInterface|null
     */
    protected $endingAt;
    
    /**
     * @var int|null
     */
    protected $trialInterval;
    
    /**
     * @var string|null
     */
    protected $trialUnit;
    
    /**
     * @var DateTimeInterface|null
     */
    protected $cancelledAt;
    
    /**
     * @var string|null
     */
    protected $cancelReason;
    
    /**
     * @var string|null
     */
    protected $dateChangeReason;
    
    /**
     * @var DateTimeInterface|null
     */
    protected $terminationDate;
    
    /**
     * @var string|null
     */
    protected $canceledBy;
    
    /**
     * @var bool|null
     */
    protected $shippingCalculateOnce;
    
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
    
    public function getSubsNumber(): ?string
    {
        return $this->subsNumber;
    }

    public function setSubsNumber(?string $subsNumber): void
    {
        $this->subsNumber = $subsNumber;
    }
    
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }
    
    public function getLineItemId(): string
    {
        return $this->lineItemId;
    }

    public function setLineItemId(string $lineItemId): void
    {
        $this->lineItemId = $lineItemId;
    }
    
    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }
    
    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
    }
    
    public function getCustomerId(): string
    {
        return $this->customerId;
    }

    public function setCustomerId(string $customerId): void
    {
        $this->customerId = $customerId;
    }
    
    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }
    
    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): void
    {
        $this->paymentReference = $paymentReference;
    }
    
    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(?int $interval): void
    {
        $this->interval = $interval;
    }
    
    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->unit = $unit;
    }
    
    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }
    
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }
    
    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): void
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
    
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    
    public function getNextDate(): ?DateTimeInterface
    {
        return $this->nextDate;
    }

    public function setNextDate(?DateTimeInterface $nextDate): void
    {
        $this->nextDate = $nextDate;
    }
    
    public function getLastDayMonth(): ?bool
    {
        return $this->lastDayMonth;
    }

    public function setLastDayMonth(bool $lastDayMonth): void
    {
        $this->lastDayMonth = $lastDayMonth;
    }
    
    public function getEndingAt(): ?DateTimeInterface
    {
        return $this->endingAt;
    }

    public function setEndingAt(?DateTimeInterface $endingAt): void
    {
        $this->endingAt = $endingAt;
    }
    
    public function getTrialInterval(): ?int
    {
        return $this->trialInterval;
    }

    public function setTrialInterval(?int $trialInterval): void
    {
        $this->trialInterval = $trialInterval;
    }
    
    public function getTrialUnit(): ?string
    {
        return $this->trialUnit;
    }

    public function setTrialUnit(?string $trialUnit): void
    {
        $this->trialUnit = $trialUnit;
    }
    
    public function getCancelledAt(): ?DateTimeInterface
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?DateTimeInterface $cancelledAt): void
    {
        $this->cancelledAt = $cancelledAt;
    }
    
    public function getCancelReason(): ?string
    {
        return $this->cancelReason;
    }

    public function setCancelReason(?string $cancelReason): void
    {
        $this->cancelReason = $cancelReason;
    }
    
    public function getDateChangeReason(): ?string
    {
        return $this->dateChangeReason;
    }

    public function setDateChangeReason(?string $dateChangeReason): void
    {
        $this->dateChangeReason = $dateChangeReason;
    }
    
    public function getTerminationDate(): ?DateTimeInterface
    {
        return $this->terminationDate;
    }

    public function setTerminationDate(?DateTimeInterface $terminationDate): void
    {
        $this->terminationDate = $terminationDate;
    }
    
    public function getCanceledBy(): ?string
    {
        return $this->canceledBy;
    }

    public function setCanceledBy(?string $canceledBy): void
    {
        $this->canceledBy = $canceledBy;
    }
    
    public function getShippingCalculateOnce(): ?bool
    {
        return $this->shippingCalculateOnce;
    }

    public function setShippingCalculateOnce(?bool $shippingCalculateOnce): void
    {
        $this->shippingCalculateOnce = $shippingCalculateOnce;
    }
}
