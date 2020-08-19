<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

/** @var \Polavi\Services\Event\EventDispatcher $eventDispatcher */

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use function Polavi\_mysql;
use Polavi\Module\Catalog\Services\ProductCollection;
use Polavi\Module\Graphql\Services\FilterFieldType;
use Polavi\Services\Di\Container;
use Polavi\Services\Http\Request;
use Polavi\Services\MiddlewareManager;
use Polavi\Module\Catalog\Services\Type\ProductImageType;
use Polavi\Module\Catalog\Middleware\Widget\FeaturedProduct\FeaturedProductWidgetMiddleware;
use Polavi\Module\Catalog\Middleware\Widget\ProductFilter\ProductFilterWidgetMiddleware;
use Polavi\Module\Catalog\Middleware\Product\View\InitMiddleware as ProductInitMiddleware;
use Polavi\Module\Catalog\Middleware\Category\View\InitMiddleware as CategoryInitMiddleware;

$eventDispatcher->addListener(
    'widget_types',
    function ($types) {
        $types[] = ['code' => 'product_filter', 'name' => 'Product Filter'];
        $types[] = ['code' => 'featured_products', 'name' => 'Featured Products'];

        return $types;
    },
    0
);

$eventDispatcher->addListener(
    'sorting_options',
    function ($options) {
        $options[] = ['code' => 'sale_price', 'name' => 'Price'];
        $options[] = ['code' => 'product.created_at', 'name' => 'Created date'];

        return $options;
    },
    0
);

$eventDispatcher->addListener('register.widget.create.middleware', function (\Polavi\Services\MiddlewareManager $mm) {
    $mm->registerMiddleware(\Polavi\Module\Catalog\Middleware\Widget\FeaturedProduct\FormMiddleware::class, 0);
    $mm->registerMiddleware(\Polavi\Module\Catalog\Middleware\Widget\ProductFilter\FormMiddleware::class, 0);
});

$eventDispatcher->addListener('register.widget.edit.middleware', function (\Polavi\Services\MiddlewareManager $mm) {
    $mm->registerMiddleware(\Polavi\Module\Catalog\Middleware\Widget\FeaturedProduct\FormMiddleware::class, 0);
    $mm->registerMiddleware(\Polavi\Module\Catalog\Middleware\Widget\ProductFilter\FormMiddleware::class, 0);
});

$eventDispatcher->addListener('register.core.middleware', function (\Polavi\Services\MiddlewareManager $mm) {
    $mm->registerMiddleware(FeaturedProductWidgetMiddleware::class, 21);
    $mm->registerMiddleware(ProductFilterWidgetMiddleware::class, 21);
});

$eventDispatcher->addListener(
    'filter.query.type',
    function (&$fields, Container $container) {
        $fields['productFilterTool'] = [
            'type' => $container->get(\Polavi\Module\Catalog\Services\Type\ProductFilterToolType::class),
            'description' => "Return data for product filter widget",
            'args' => [
                'filters' => Type::listOf($container->get(FilterFieldType::class))
            ],
            'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                // Get the root product collection filter
                $filters = $container
                    ->get(Symfony\Component\HttpFoundation\Session\Session::class)
                    ->get("productCollectionQuery");
                $args["filters"] = $args["filters"] ?? $filters;

                return $container
                    ->get(ProductCollection::class)
                    ->getProductIdArray($rootValue, $args, $container, $info);
            }
        ];

        $fields['productImages'] = [
            'type' => new ObjectType([
                'name' => "ProductImageList",
                'fields' => [
                    'images' => Type::listOf($container->get(ProductImageType::class)),
                    'productName' => Type::string()
                ],
                'resolveField' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                    return isset($rootValue[$info->fieldName]) ? $rootValue[$info->fieldName] : null;
                }
            ]),
            'description' => "Return a list of product image",
            'args' => [
                'productId' =>  Type::nonNull(Type::int())
            ],
            'resolve' => function ($rootValue, $args) {
                $conn = _mysql();
                $mainImage = $conn
                    ->getTable('product')
                    ->addFieldToSelect('image')
                    ->where('product_id', '=', $args['productId'])
                    ->fetchOneAssoc();

                $result['images'] = [];
                if ($mainImage['image']) {
                    $result['images'][] = ['path' => $mainImage['image'], 'isMain'=> true];
                }
                $stm = $conn->getTable('product_image')
                    ->addFieldToSelect('image')
                    ->where('product_image_product_id', '=', $args['productId'])
                    ->fetchAllAssoc();
                foreach ($stm as $row) {
                    $result['images'][] = ['path'=>$row['image']];
                }
                $productName = $conn->getTable('product_description')
                    ->where('product_description_product_id', '=', $args['productId'])
                    ->fetchOneAssoc();
                if ($productName) {
                    $result['productName'] = $productName['name'];
                }

                return $result;
            }
        ];

        $fields['productTierPrice'] = [
            'type' => Type::listOf($container->get(\Polavi\Module\Catalog\Services\Type\ProductTierPriceType::class)),
            'description' => "Return a list of product tier price",
            'args' => [
                'productId' =>  Type::nonNull(Type::int()),
                'qty' =>  Type::int(),
            ],
            'resolve' => function ($rootValue, $args, Container $container) {
                $query = _mysql()->getTable('product_price')
                    ->where('product_price_product_id', '=', $args['productId']);
                $query->andWhere('customer_group_id', '<', 1000);
                if (!$container->get(Request::class)->isAdmin()) {
                    $query->andWhere('active_from', 'IS', null, '((')
                        ->orWhere('active_from', '<', date("Y-m-d H:i:s"), null, ')')
                        ->andWhere('active_to', 'IS', null, '(')
                        ->orWhere('active_to', '>', date("Y-m-d H:i:s"), null, '))');

                    $customerGroupId = $container
                        ->get(Request::class)
                        ->getCustomer()
                        ->isLoggedIn()
                        ? $container
                            ->get(Request::class)
                            ->getCustomer()
                            ->getData('group_id')
                        ?? 1 : 999;
                    $query->andWhere('customer_group_id', '=', $customerGroupId);
                }

                if (isset($args['qty'])) {
                    $query->andWhere('qty', '>=', $args['qty']);
                } else {
                    $query->andWhere('qty', '>=', 1);
                }

                return $query->fetchAllAssoc(['sort_by'=>'qty', 'sort_order'=>'ASC']);
            }
        ];

        $fields['potentialVariants'] = [
            'type' => Type::listOf($container->get(\Polavi\Module\Catalog\Services\Type\ProductType::class)),
            'description' => "Return a list of potential variants",
            'args' => [
                'attributeGroupId' =>  Type::nonNull(Type::int()),
                'name' => Type::string()
            ],
            'resolve' => function ($rootValue, $args) {
                if (!$args['name']) {
                    return _mysql()->getTable("product")
                        ->leftJoin('product_description')
                        ->where("product.group_id", "=", $args["attributeGroupId"])
                        ->andWhere("product.variant_group_id", "IS", null)
                        ->fetchAssoc(["limit" => 100]);
                } else {
                    return _mysql()->getTable("product")
                        ->leftJoin('product_description')
                        ->where("product.group_id", "=", $args["attributeGroupId"])
                        ->andWhere("product.variant_group_id", "IS", null)
                        ->andWhere("product_description.name", "LIKE", "%{$args["name"]}%")
                        ->fetchAssoc(["limit" => 100]);
                }
            }
        ];
    },
    5
);

$eventDispatcher->addListener(
    "admin_menu",
    function (array $items) {
        return array_merge($items, [
            [
                "id" => "product_add_new",
                "sort_order" => 10,
                "url" => \Polavi\generate_url("product.create"),
                "title" => "New Product",
                "icon" => "cubes",
                "parent_id" => "quick_links"
            ],
            [
                "id" => "catalog",
                "sort_order" => 20,
                "url" => null,
                "title" => "Catalog",
                "parent_id" => null
            ],
            [
                "id" => "catalog_products",
                "sort_order" => 10,
                "url" => \Polavi\generate_url("product.grid"),
                "title" => "Products",
                "icon" => "boxes",
                "parent_id" => "catalog"
            ],
            [
                "id" => "catalog_categories",
                "sort_order" => 20,
                "url" => \Polavi\generate_url("category.grid"),
                "title" => "Categories",
                "icon" => "tags",
                "parent_id" => "catalog"
            ],
            [
                "id" => "catalog_attributes",
                "sort_order" => 30,
                "url" => \Polavi\generate_url("attribute.grid"),
                "title" => "Attributes",
                "icon" => "ruler-combined",
                "parent_id" => "catalog"
            ],
            [
                "id" => "catalog_attribute_groups",
                "sort_order" => 40,
                "url" => \Polavi\generate_url("attribute.group.grid"),
                "title" => "Attribute groups",
                "icon" => "tags",
                "parent_id" => "catalog"
            ]
        ]);
    },
    0
);

$eventDispatcher->addListener(
    'before_delete_attribute_group',
    function ($rows) {
        foreach ($rows as $row) {
            if ($row['attribute_group_id'] == 1) {
                throw new Exception("Can not delete 'Default' attribute group");
            }
        }
    }
);

$eventDispatcher->addListener(
    'filter.mutation.type',
    function (&$fields) {
        $fields['unlinkVariant'] = [
            'args' => [
                'productId' => Type::nonNull(Type::int())
            ],
            'type' => new ObjectType([
                'name'=> 'unlinkVariantOutPut',
                'fields' => [
                    'status' => Type::nonNull(Type::boolean()),
                    'message' => Type::string(),
                    'productId' => Type::int()
                ]
            ]),
            'resolve' => function ($rootValue, $args, Container $container, ResolveInfo $info) {
                $conn = _mysql();
                if ($container->get(Request::class)->isAdmin() == false) {
                    return ['status' => false, 'message' => 'Permission denied'];
                }
                $product = $conn->getTable("product")->load($args['productId']);

                if (!$product) {
                    return ['status' => true, 'message' => 'Product does not exist'];
                }

                $conn
                    ->getTable("product")
                    ->where("product_id", "=", $args["productId"])
                    ->update(["variant_group_id" => null, "visibility" => null]);

                return ['status' => true, 'productId' => $args["productId"]];
            }
        ];
    },
    5
);

// Remove product from variant group if attribute option was deleted
$productIds = [];
$eventDispatcher->addListener('before_delete_attribute_option',  function ($affectedRows) use (&$productIds) {
    foreach ($affectedRows as $row) {
        $stm = _mysql()
                ->getTable('product_attribute_value_index')
                ->addFieldToSelect("product_id")
                ->where('attribute_id', '=', $row["attribute_id"])
                ->andWhere('option_id', '=', $row["attribute_option_id"]);
        $productIds = [];
        while ($row = $stm->fetch()) {
            $productIds[] = $row['product_id'];
        }
    }
});

$eventDispatcher->addListener(
    'after_delete_attribute_option',
    function ($affectedRows, \Polavi\Services\Db\Processor $processor) use (&$productIds) {
        foreach ($affectedRows as $row) {
            $groupIds = [];
            $stm = $processor->getTable("variant_group")
                ->where("attribute_one", "=", $row["attribute_id"])
                ->orWhere("attribute_two", "=", $row["attribute_id"])
                ->orWhere("attribute_three", "=", $row["attribute_id"])
                ->orWhere("attribute_four", "=", $row["attribute_id"])
                ->orWhere("attribute_five", "=", $row["attribute_id"]);
            while ($row = $stm->fetch()) {
                $groupIds[] = $row['variant_group_id'];
            }
            if (!$groupIds || !$productIds) {
                return true;
            }
            $processor->getTable("product")
                ->where("variant_group_id", "IN", $groupIds)
                ->andWhere("product_id", "IN", $productIds)
                ->update(["variant_group_id" => null]);

            return true;
        }
        return true;
    }
);

$eventDispatcher->addListener('breadcrumbs_items', function (array $items) {
    $container = \Polavi\the_container();
    if (in_array($container->get(Request::class)->get("_matched_route"), ["category.view", "category.view.pretty"])) {
        $category = MiddlewareManager::getDelegate(CategoryInitMiddleware::class, null);
        if ($category == null) {
            $category = _mysql()->getTable('category')
                ->leftJoin('category_description')
                ->where('category.category_id', '=', $container->get(Request::class)->attributes->get('id'))
                ->fetchOneAssoc();
        }

        $items[] = ["sort_order" => 1, "title"=> $category["name"], "link" => null];
    }

    if (in_array($container->get(Request::class)->get("_matched_route"), ["product.view", "product.view.pretty"])) {
        $product = MiddlewareManager::getDelegate(ProductInitMiddleware::class, null);
        if ($product == null) {
            $product = _mysql()->getTable('product')
                ->leftJoin('product_description')
                ->where('product.product_id', '=', $container->get(Request::class)->attributes->get('id'))
                ->fetchOneAssoc();
        }

        $items[] = ["sort_order"=> 1, "title"=> $product["name"], "link"=> null];
    }

    return $items;
});