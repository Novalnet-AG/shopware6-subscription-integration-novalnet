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

namespace Novalnet\NovalnetSubscription\TwigFilter;

use Novalnet\NovalnetSubscription\Helper\Helper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension relate to PHP code and used by the profiler and the default exception templates.
 */
class Filter extends AbstractExtension
{
	
	/**
     * @var EntityRepository
     */
    public $productRepository;
    
    /**
     * @var Helper
     */
    public $helper;
	
	public function __construct(
        EntityRepository $productRepository,
        Helper $helper
    ) {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
    }
    
    public function getFilters()
    {
        return [
            new TwigFilter('getParentProduct', [$this, 'getParentProduct']),
            new TwigFilter('getActiveSubscription', [$this->helper, 'getActiveSubscription']),
        ];
    }

    /**
     * Return the parent product entity
     *
     * @param string $productId
     *
     * @return array
     */
    public function getParentProduct(string $productId): array
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('novalnetConfiguration');
        $product  = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

        return $product->getExtensions();
    }
}
