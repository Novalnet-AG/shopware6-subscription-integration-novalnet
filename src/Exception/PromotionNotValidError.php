<?php declare(strict_types=1);

namespace Novalnet\NovalnetSubscription\Exception;

use Shopware\Core\Checkout\Cart\Error\Error;

class PromotionNotValidError extends Error
{
    private const KEY = 'auto-promotion-not-valid';

    /**
     * @var string
     */
    protected $name;

    public function __construct(string $name)
    {
        $this->name = $name;

        $this->message = sprintf('For free trial products, the promotion was not valid!');

        parent::__construct($this->message);
    }

    public function getParameters(): array
    {
        return ['name' => $this->name];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getId(): string
    {
        return sprintf('%s-%s', self::KEY, $this->name);
    }

    public function getMessageKey(): string
    {
        return self::KEY;
    }

    public function getLevel(): int
    {
        return self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }
}
