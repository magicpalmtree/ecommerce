<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Catalog\Services\Type;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use function Polavi\dispatch_event;
use Polavi\Services\Di\Container;
use GraphQL\Type\Definition\Type;

class ProductTierPriceType extends ObjectType
{
    public function __construct(Container $container)
    {
        $config = [
            'name' => 'ProductTierPrice',
            'fields' => function () use ($container){
                $fields = [
                    'product_price_id' => [
                        'type' => Type::nonNull(Type::id())
                    ],
                    'product_id' => [
                        'type' => Type::nonNull(Type::id()),
                        'resolve' => function ($value) {
                            return isset($value['product_price_product_id'])
                                ? $value['product_price_product_id'] : null;
                        }
                    ],
                    'customer_group_id' => [
                        'type' => Type::nonNull(Type::string())
                    ],
                    'tier_price' => [
                        'type' => Type::nonNull(Type::float())
                    ],
                    'qty' => [
                        'type' => Type::nonNull(Type::int())
                    ],
                    'active_from' => [
                        'type' => Type::string()
                    ],
                    'active_to' => [
                        'type' => Type::string()
                    ]
                ];

                dispatch_event('filter.productTierPrice.type', [&$fields]);

                return $fields;
            },
            'resolveField' => function ($value, $args, Container $container, ResolveInfo $info) {
                return isset($value[$info->fieldName]) ? $value[$info->fieldName] : null;
            }
        ];

        parent::__construct($config);
    }

}
