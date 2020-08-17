<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Discount\Services;


use GraphQL\Type\Definition\ResolveInfo;
use function Polavi\_mysql;
use Polavi\Services\Di\Container;
use Polavi\Services\Grid\CollectionBuilder;

class CouponCollection extends CollectionBuilder
{
    /**@var Container $container*/
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;

        $this->init(_mysql()->getTable('coupon'));

        $this->defaultFilters();
    }

    protected function defaultFilters()
    {
        $this->addFilter('coupon', function ($args) {
            $this->collection->andWhere('coupon.coupon', $args['operator'], $args['value']);
        });

        $this->addFilter('description', function ($args) {
            $this->collection->andWhere('coupon.description', $args['operator'], $args['value']);
        });

        $this->addFilter('free_shipping', function ($args) {
            $this->collection->andWhere('coupon.free_shipping', $args['operator'], $args['value']);
        });

        $this->addFilter('status', function ($args) {
            $this->collection->andWhere('coupon.status', $args['operator'], (int)$args['value']);
        });

//        $this->addFilter('start_date', function ($args) use ($isAdmin) {
//            $this->collection->andWhere('coupon.start_date', $args['operator'], (int)$args['value']);
//        });
//
//        $this->addFilter('end_date', function ($args) use ($isAdmin) {
//            $this->collection->andWhere('coupon.end_date', $args['operator'], (int)$args['value']);
//        });
        $this->addFilter('page', function ($args) {
            if ($args['operator'] !== "=")
                return;
            $this->setPage((int)$args['value']);
        });

        $this->addFilter('limit', function ($args) {
            if ($args['operator'] !== "=")
                return;
            $this->setLimit((int)$args['value']);
        });

        $this->addFilter('sortBy', function ($args) {
            if ($args['operator'] !== "=")
                return;
            $this->setSortBy($args['value']);
        });

        $this->addFilter('sortOrder', function ($args) {
            if ($args['operator'] !== "=")
                return;
            $this->setSortOrder($args['value']);
        });
    }

    public function getData($rootValue, $args, Container $container, ResolveInfo $info)
    {
        $filters = $args['filter'] ?? [];
        foreach ($filters as $key => $arg)
            $this->applyFilter($key, $arg);

        return [
                'coupons' => $this->load(),
                'total' => $this->getTotal(),
                'currentFilter' => json_encode($filters, JSON_NUMERIC_CHECK)
            ];
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getCouponIdArray($rootValue, $args, Container $container, ResolveInfo $info)
    {
        $filters = $args['filter'] ?? [];
        foreach ($filters as $key => $arg)
            $this->applyFilter($key, $arg);

        $collection = clone $this->collection;
        $ids = [];
        while ($row = $collection->addFieldToSelect("coupon.coupon_id")->fetch()) {
            $ids[] = $row['coupon_id'];
        }

        return $ids;
    }
}