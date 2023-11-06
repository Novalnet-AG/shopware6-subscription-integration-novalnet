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

namespace Novalnet\NovalnetSubscription\Core\Content\Product\SalesChannel\Detail;

use Shopware\Core\Content\Product\SalesChannel\Detail\AbstractProductDetailRoute;
use Shopware\Core\Content\Product\SalesChannel\Detail\ProductDetailRouteResponse;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Request;

class ProductDetailRouteDecorator extends AbstractProductDetailRoute
{
    /**
     * @var AbstractProductDetailRoute
     */
    private $originalRoute;

    public function __construct(AbstractProductDetailRoute $originalRoute)
    {
        $this->originalRoute = $originalRoute;
    }
    public function getDecorated(): AbstractProductDetailRoute
    {
        return $this->originalRoute;
    }

    public function load(string $productId, Request $request, SalesChannelContext $context, Criteria $criteria): ProductDetailRouteResponse
    {
        $criteria->addAssociation('novalnetConfiguration');

        return $this->originalRoute->load($productId, $request, $context, $criteria);
    }
}
