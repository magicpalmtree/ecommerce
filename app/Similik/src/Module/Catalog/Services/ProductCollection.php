<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Module\Catalog\Services;


use GraphQL\Type\Definition\ResolveInfo;
use function Similik\_mysql;
use function Similik\get_default_language_Id;
use Similik\Services\Di\Container;
use Similik\Services\Grid\CollectionBuilder;
use Similik\Services\Http\Request;

class ProductCollection extends CollectionBuilder
{
    /**@var Container $container*/
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $collection = _mysql()->getTable('product')
            ->leftJoin('product_description', null, [
                [
                    'column'      => "product_description.language_id",
                    'operator'    => "=",
                    'value'       => get_default_language_Id(),
                    'ao'          => 'and',
                    'start_group' => null,
                    'end_group'   => null
                ]
            ]);

        if($this->container->get(Request::class)->isAdmin() == false) {
            $collection->where('product.status', '=', 1);
        }

        $this->init($collection);

        $this->defaultFilters();
    }

    protected function defaultFilters()
    {
        $isAdmin = $this->container->get(Request::class)->isAdmin();
        $this->addFilter('price', function($args) {
            if($args['operator'] == "BETWEEN") {
                $arr = explode("AND", $args['value']);
                $from = (float) trim($arr[0]);
                $to = isset($arr[1]) ? (float) trim($arr[1]) : null;
                $this->getCollection()->andWhere('product.price', '>=', $from);
                if($to)
                    $this->getCollection()->andWhere('product.price', '<=', $to);
            } else {
                $this->getCollection()->andWhere('product.price', $args['operator'], $args['value']);
            }
        });

        $this->addFilter('qty', function($args) use ($isAdmin) {
            if($isAdmin == false)
                return;
            $this->collection->andWhere('product.qty', $args['operator'], $args['value']);
        });

        $this->addFilter('name', function($args) {
            $this->collection->andWhere('product_description.name', $args['operator'], $args['value']);
        });

        $this->addFilter('status', function($args) use ($isAdmin) {
            if($isAdmin == false)
                return;
            $this->collection->andWhere('product.status', $args['operator'], (int)$args['value']);
        });

        $this->addFilter('category', function($args) use ($isAdmin) {
            if($args['operator'] == "IN") {
                $ids = array_map('intval', explode(',', $args['value']));
                $stm = _mysql()
                    ->getTable('product_category')
                    ->where('category_id', 'IN', $ids);
                $productIds = [];
                while ($row = $stm->fetch()) {
                    $productIds[] = $row['product_id'];
                }
                $this->getCollection()->where('product.product_id', 'IN', $productIds);
            }
        });

        $this->addFilter('sku', function($args) use ($isAdmin) {
            if($args['operator'] == "IN") {
                $skus = explode(',', $args['value']);
                $this->getCollection()->andWhere('product.sku', 'IN', $skus);
            } else {
                $this->getCollection()->andWhere('product.sku', $args['operator'], (int)$args['value']);
            }
        });

        $filterAbleAttributes = [1, 7, 8, 9];
        $conn = _mysql();
        $tmp = $conn->getTable('attribute')
            ->addFieldToSelect('attribute_code')
            ->where('attribute_id', 'IN', $filterAbleAttributes);
        while($row = $tmp->fetch()) {
            $this->addFilter($row['attribute_code'], function($args) use ($isAdmin, $conn) {
                if($args['operator'] == "IN") {
                    $ids = array_map('intval', explode(',', $args['value']));
                    $stm = $conn->getTable('product_attribute_value_index')
                        ->addFieldToSelect('product_id')
                        ->where('option_id', 'IN', $ids);
                    $productIds = [];
                    while ($row = $stm->fetch()) {
                        $productIds[] = $row['product_id'];
                    }
                    $this->getCollection()->andWhere('product.product_id', 'IN', $productIds);
                }
            });
        }

    }

    public function getData($rootValue, $args, Container $container, ResolveInfo $info)
    {
        $filters = $args['filter'] ?? [];
        foreach ($filters as $key => $arg)
            $this->applyFilter($key, $arg);

        return [
                'products' => $this->load(),
                'total' => $this->getTotal(),
                'currentFilter' => json_encode($filters, JSON_NUMERIC_CHECK)
            ];
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getProductIdArray($rootValue, $args, Container $container, ResolveInfo $info)
    {
        $filters = $args['filter'] ?? [];
        foreach ($filters as $key => $arg)
            $this->applyFilter($key, $arg);

        $collection = clone $this->collection;
        $ids = [];
        while ($row = $collection->addFieldToSelect("product.product_id")->fetch()) {
            $ids[] = $row['product_id'];
        }

        return $ids;
    }
}