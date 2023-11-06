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

class NovalnetSubsCycleEntity extends Entity
{
    use EntityIdTrait;
    
    /**
     * @var string
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $subsId;
    
    /**
     * @var string
     */
    protected $orderId;
    
    /**
     * @var float
     */
    protected $amount;
    
    /**
     * @var int|null
     */
    protected $interval;
    
    /**
     * @var string
     */
    protected $period;
    
    /**
     * @var string|null
     */
    protected $paymentMethodId;
    
    /**
     * @var int
     */
    protected $cycles;
    
    /**
     * @var DateTimeInterface|null
     */
    protected $cycleDate;
    
    /**
     * @var string
     */
    protected $status;
    
    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }
    
    public function getSubsId(): ?string
    {
        return $this->subsId;
    }

    public function setSubsId(?string $subsId): void
    {
        $this->subsId = $subsId;
    }
    
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }
    
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }
    
    public function getInterval(): ?int
    {
        return $this->interval;
    }

    public function setInterval(?int $interval): void
    {
        $this->interval = $interval;
    }
    
    public function getPeriod(): string
    {
        return $this->period;
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }
    
    public function getPaymentMethodId(): ?string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }
    
    public function getCycles(): ?int
    {
        return $this->cycles;
    }

    public function setCycles(int $cycles): void
    {
        $this->cycles = $cycles;
    }
    
    public function getCycleDate(): ?DateTimeInterface
    {
        return $this->cycleDate;
    }

    public function setCycleDate(?DateTimeInterface $cycleDate): void
    {
        $this->cycleDate = $cycleDate;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
