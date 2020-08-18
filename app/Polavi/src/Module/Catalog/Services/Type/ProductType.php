<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Catalog\Services\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use function Polavi\_mysql;
use function Polavi\dispatch_event;
use Polavi\Module\Catalog\Services\ProductCollection;
use Polavi\Services\Di\Container;
use Polavi\Services\Db\Processor;
use Polavi\Services\Db\Table;
use GraphQL\Type\Definition\Type;
use Polavi\Services\Http\Request;
use Polavi\Services\Routing\Router;

class ProductType extends ObjectType
{
    public function __construct(Container $container)
    {
        $config = [
            'name' => 'Product',
            'fields' => function () use ($container) {
                $fields = [
                    'product_id' => [
                        'type' => Type::nonNull(Type::id())
                    ],
                    'sku' => [
                        'type' => Type::nonNull(Type::string())
                    ],
                    'variant_group_id' => [
                        'type' => Type::int()
                    ],
                    'visibility' => [
                        'type' => Type::int()
                    ],
                    'price' => [
                        'type' => Type::nonNull(Type::float())
                    ],
                    'salePrice' => [
                        'type' => Type::nonNull(Type::float()),
                        'resolve' => function ($product) {
                            if (isset($product['sale_price'])) {
                                return $product['sale_price'];
                            }
                            return $product['price'];
                        }
                    ],
                    'categories' => [
                        'type' => Type::listOf(new ObjectType([
                            'name'=> "Product's categories",
                            'fields' => [
                                'category_id' => Type::nonNull(Type::int()),
                                'parent_id' => Type::int(),
                                'name' => Type::string(),
                                'status'=> Type::int()
                            ]
                        ])),
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            $categoryIds = [];
                            $conn = _mysql();
                            $stmt = $conn->getTable('product_category')
                                ->where('product_id', '=', (int)$product['product_id']);
                            while ($row = $stmt->fetch()) {
                                $categoryIds[] = $row['category_id'];
                            }
                            if (!$categoryIds) {
                                return null;
                            }
                             //Problem is here, DataLoader?
                            $categoryTable = new Table('category', $container->get(Processor::class));
                            $categoryTable->leftJoin('category_description');

                            return $categoryTable->where('category.category_id', 'IN', $categoryIds)->fetchAllAssoc();
                        }
                    ],
                    'weight' => [
                        'type' => Type::nonNull(Type::float())
                    ],
                    'qty' => [
                        'type' => Type::int(),
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            if ($container->get(Request::class)->isAdmin() == false) {
                                return null;
                            }
                            return $product['qty'];
                        }
                    ],
                    'manage_stock' => [
                        'type' => Type::int(),
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            if ($container->get(Request::class)->isAdmin() == false) {
                                return null;
                            }
                            return $product['manage_stock'];
                        }
                    ],
                    'stock_availability' => [
                        'type' => Type::nonNull(Type::int())
                    ],
                    'tax_class' => [
                        'type' => Type::int()
                    ],
                    'status' => [
                        'type' => Type::nonNull(Type::int())
                    ],
                    'image' => [
                        'type' => $container->get(ProductImageType::class),
                        'resolve' => function ($product) {
                            return $product['image']
                                ? ['path' => $product['image'], 'isMain'=> true]
                                : ['path'=> 'upload/placeholder.png', 'isMain'=> true];
                        }
                    ],
                    'name' => [
                        'type' => Type::string()
                    ],
                    'short_description' => [
                        'type' => Type::string()
                    ],
                    'description' => [
                        'type' => Type::string()
                    ],
                    'seo_key' => [
                        'type' => Type::string()
                    ],
                    'url' => [
                        'type' => Type::string(),
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            if (!preg_match('/^[\.a-zA-Z0-9\-_+]+$/', $product['seo_key'])) {
                                return $container->get(Router::class)
                                    ->generateUrl('product.view', ["id"=>$product['product_id']]);
                            } else {
                                return $container->get(Router::class)
                                    ->generateUrl('product.view.pretty', ["slug"=>$product['seo_key']]);
                            }
                        }
                    ],
                    'editUrl' => [
                        'type' => Type::string(),
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            if ($container->get(Request::class)->isAdmin() == false) {
                                return null;
                            }
                            return $container->get(Router::class)
                                ->generateUrl('product.edit', ["id"=>$product['product_id']]);
                        }
                    ],
                    'deleteUrl' => [
                        'type' => Type::string(),
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            if ($container->get(Request::class)->isAdmin() == false) {
                                return null;
                            }
                            return $container->get(Router::class)
                                ->generateUrl('product.delete', ["id"=>$product['product_id']]);
                        }
                    ],
                    'meta_title' => [
                        'type' => Type::string()
                    ],
                    'meta_description' => [
                        'type' => Type::string()
                    ],
                    'meta_keywords' => [
                        'type' => Type::string()
                    ],
                    'group_id' => [
                        'type' => Type::nonNull(Type::int())
                    ],
                    'options' => [
                        'type' => Type::listOf($container->get(CustomOptionType::class)),
                        'description' => 'List of custom option',
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            return $container->get(Processor::class)
                                ->getTable('product_custom_option')
                                ->where('product_custom_option_product_id', '=', $product['product_id'])
                                ->fetchAllAssoc();
                        }
                    ],
                    'attributes' => [
                        'type' => Type::listOf($container->get(ProductAttributeIndex::class)),
                        'description' => 'List of attribute and value',
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            return _mysql()->getTable('product_attribute_value_index')
                                ->leftJoin('attribute')
                                ->where('product_id', '=', $product['product_id'])->fetchAllAssoc();
                        }
                    ],
                    'variants' => [
                        'type' => Type::listOf($this),
                        'description' => 'List of custom option',
                        'resolve' => function ($product, $args, Container $container, ResolveInfo $info) {
                            $conn = _mysql();
                            $group = $conn->getTable("variant_group")->load($product["variant_group_id"]);
                            if (!$group) {
                                return [];
                            }

                            $productCollection = new ProductCollection($container);
                            $productCollection
                                ->getCollection()
                                ->where("variant_group_id", "=", $group["variant_group_id"]);

                            return $productCollection->getCollection()->fetchAllAssoc();
                        }
                    ],
                    'created_at' => [
                        'type' => Type::string()
                    ],
                    'updated_at' => [
                        'type' => Type::string()
                    ]
                ];

                dispatch_event('filter.product.type', [&$fields]);

                return $fields;
            },
            'resolveField' => function ($value, $args, Container $container, ResolveInfo $info) {
                return isset($value[$info->fieldName]) ? $value[$info->fieldName] : null;
            }
        ];

        parent::__construct($config);
    }
}
