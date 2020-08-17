<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Cms\Services;


use GraphQL\Type\Definition\ResolveInfo;
use function Polavi\_mysql;
use Polavi\Services\Di\Container;
use Polavi\Services\Grid\CollectionBuilder;
use Polavi\Services\Http\Request;

class WidgetCollection extends CollectionBuilder
{
    /**@var Container $container*/
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $collection = _mysql()->getTable('cms_widget');
        if ($this->container->get(Request::class)->isAdmin() == false)
            $collection->where('status', '=', 1);
        $this->init(
            $collection
        );

        $this->defaultFilters();
    }

    protected function defaultFilters()
    {
        $this->addFilter('type', function ($args) {
            $this->collection->andWhere('cms_widget.type', $args['operator'], $args['value']);
        });

        $this->addFilter('name', function ($args) {
            $this->collection->andWhere('cms_widget.name', $args['operator'], $args['value']);
        });

        $this->addFilter('status', function ($args) {
            $this->collection->andWhere('cms_widget.status', $args['operator'], $args['value']);
        });

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
        $filters = $args['filters'] ?? [];
        foreach ($filters as $key => $arg)
            $this->applyFilter($arg["key"], $arg);

        return [
            'widgets' => $this->load(),
            'total' => $this->getTotal(),
            'currentFilter' => json_encode($filters, JSON_NUMERIC_CHECK)
        ];
    }

    public function getCollection()
    {
        return $this->collection;
    }
}