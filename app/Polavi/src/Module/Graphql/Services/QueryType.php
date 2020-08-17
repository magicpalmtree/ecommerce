<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Graphql\Services;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use function Polavi\_mysql;
use function Polavi\dispatch_event;
use Polavi\Module\Catalog\Services\AttributeCollection;
use Polavi\Module\Catalog\Services\AttributeGroupCollection;
use Polavi\Module\Catalog\Services\CategoryCollection;
use Polavi\Module\Catalog\Services\ProductCollection;
use Polavi\Module\Catalog\Services\Type\AttributeCollectionType;
use Polavi\Module\Catalog\Services\Type\AttributeGroupCollectionType;
use Polavi\Module\Catalog\Services\Type\AttributeType;
use Polavi\Module\Catalog\Services\Type\CategoryCollectionType;
use Polavi\Module\Catalog\Services\Type\ProductCollectionType;
use Polavi\Module\Catalog\Services\Type\AttributeGroupType;
use Polavi\Module\Catalog\Services\Type\CategoryType;
use Polavi\Module\Catalog\Services\Type\ProductAttributeIndex;
use Polavi\Module\Catalog\Services\Type\ProductType;
use Polavi\Module\Checkout\Services\Cart\Cart;
use Polavi\Module\Checkout\Services\Type\CartType;
use Polavi\Module\Tax\Services\Type\TaxClassType;
use Polavi\Services\Db\Processor;
use Polavi\Services\Di\Container;
use Polavi\Services\Http\Request;

class QueryType extends ObjectType
{
    public function __construct(Container $container)
    {
        $config = [
        'name' => 'Query',
        'fields' => function () use ($container) {
            $fields = [
                'product' => [
                    'type' => $container->get(ProductType::class),
                    'description' => 'Return a product',
                    'args' => [
                        'id' => Type::nonNull(Type::id())
                    ],
                    'resolve' => function ($product, $args, Container $container, ResolveInfo $info)  {
                        $productTable = _mysql()->getTable('product');
                        $productTable->leftJoin('product_description');
                        $productTable->where('product.product_id', '=', $args['id']);
                        if ($container->get(Request::class)->isAdmin() == false)
                            $productTable->andWhere('product.status', '=', 1);

                        return $productTable->fetchOneAssoc();
                    }
                ],
                'productCollection' => [
                    'type' => $container->get(ProductCollectionType::class),
                    'description' => "Return list of products and total count",
                    'args' => [
                        'filters' =>  Type::listOf($container->get(FilterFieldType::class))
                    ],
                    'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                        return $container->get(ProductCollection::class)->getData($rootValue, $args, $container, $info);
                    }
                ],
                'category' => [
                    'type' => $container->get(CategoryType::class),
                    'description' => 'Return a category',
                    'args' => [
                        'id' => Type::nonNull(Type::id())
                    ],
                    'resolve' => function ($value, $args, Container $container, ResolveInfo $info) {
                        $categoryTable = _mysql()->getTable('category');
                        $categoryTable->leftJoin('category_description');
                        return $categoryTable->where('category.category_id', '=', $args['id'])->fetchOneAssoc();
                    }
                ],
                'categoryCollection' => [
                    'type' => $container->get(CategoryCollectionType::class),
                    'description' => "Return list of categories and total count",
                    'args' => [
                        'filters' =>  Type::listOf($container->get(FilterFieldType::class))
                    ],
                    'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                        return $container->get(CategoryCollection::class)->getData($rootValue, $args, $container, $info);
                    }
                ],
                'attribute' => [
                    'type' => $container->get(AttributeType::class),
                    'description' => 'Return an attribute',
                    'args' => [
                        'id' => Type::nonNull(Type::id())
                    ],
                    'resolve' => function ($value, $args, Container $container, ResolveInfo $info) {
                        return _mysql()->getTable('attribute')->where('attribute_id', '=', $args['id'])->fetchOneAssoc();
                    }
                ],
                'attributeCollection' => [
                    'type' => $container->get(AttributeCollectionType::class),
                    'description' => "Return list of attribute and total count",
                    'args' => [
                        'filters' =>  Type::listOf($container->get(FilterFieldType::class))
                    ],
                    'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                        return $container->get(AttributeCollection::class)->getData($rootValue, $args, $container, $info);
                    }
                ],
                'attributeGroup' => [
                    'type' => $container->get(AttributeGroupType::class),
                    'description' => 'Return an attribute group',
                    'args' => [
                        'id' => Type::nonNull(Type::id())
                    ],
                    'resolve' => function ($value, $args, Container $container, ResolveInfo $info) {
                        return _mysql()->getTable('attribute_group')->where('attribute_group_id', '=', $args['id'])->fetchOneAssoc();
                    }
                ],
                'attributeGroupCollection' => [
                    'type' => $container->get(AttributeGroupCollectionType::class),
                    'description' => "Return list of attribute group and total count",
                    'args' => [
                        'filters' =>  Type::listOf($container->get(FilterFieldType::class))
                    ],
                    'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                        return $container->get(AttributeGroupCollection::class)->getData($rootValue, $args, $container, $info);
                    }
                ],
                'productAttributeIndex' => [
                    'type' => Type::listOf($container->get(ProductAttributeIndex::class)),
                    'description' => 'Return attribute value of a specified product',
                    'args' => [
                        'product_id' => Type::nonNull(Type::id())
                    ],
                    'resolve' => function ($value, $args, Container $container, ResolveInfo $info) {
                        return $container->get(Processor::class)->getTable('product_attribute_value_index')
                            ->leftJoin('attribute')
                            ->where('product_id', '=', $args['product_id'])
                            ->fetchAllAssoc();
                    }
                ],
                'cart' => [
                    'type'=> $container->get(CartType::class),
                    'description' => 'Return shopping cart',
                    'resolve' => function ($value, $args, Container $container, ResolveInfo $info) {
                        return $container->get(Cart::class)->toArray();
                    }
                ],
                'taxClass' => [
                    'type' => $container->get(TaxClassType::class),
                    'description' => "Return a tax class",
                    'args' => [
                        'id' =>  Type::nonNull(Type::int())
                    ],
                    'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                        // Authentication example
                        if ($container->get(Request::class)->isAdmin() == false)
                            return null;
                        else
                            return _mysql()->getTable('tax_class')->load($args['id']);
                    }
                ],
                'taxClasses' => [
                    'type' => Type::listOf($container->get(TaxClassType::class)),
                    'description' => "Return all tax class",
                    'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                        // Authentication example
                        if ($container->get(Request::class)->isAdmin() == false)
                            return [];
                        else
                            return _mysql()->getTable('tax_class')->fetchAllAssoc();
                    }
                ]
            ];

            dispatch_event('filter.query.type', [&$fields, $container]);

            return $fields;
        },
        'resolveField' => function ($value, $args, Container $container, ResolveInfo $info) {
            return isset($value[$info->fieldName]) ? $value[$info->fieldName] : null;
        }
    ];
        parent::__construct($config);
    }
}
