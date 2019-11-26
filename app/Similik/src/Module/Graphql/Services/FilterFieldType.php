<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Module\Graphql\Services;


use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\Type;
use function Similik\dispatch_event;
use Similik\Services\Di\Container;

class FilterFieldType extends InputObjectType
{
    public function __construct(Container $container)
    {
        $config = [
            'name'=> 'FilterFieldType',
            'fields' => function() use($container) {
                $fields = [
                    'operator' => Type::nonNull(Type::string()),
                    'value' => Type::nonNull(Type::string())
                ];

                return $fields;
            }
        ];
        parent::__construct($config);
    }
}