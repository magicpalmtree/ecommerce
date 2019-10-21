<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Module\Catalog\Middleware\Product\Edit;

use function Similik\get_default_language_Id;
use function Similik\get_js_file_url;
use Similik\Module\Graphql\Services\GraphqlExecutor;
use Similik\Services\Http\Request;
use Similik\Services\Http\Response;
use Similik\Middleware\MiddlewareAbstract;

class AttributeMiddleware extends MiddlewareAbstract
{
    const FORM_ID = 'product-edit-form';
    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if($response->hasWidget('product_edit_attributes'))
            return $delegate;

        $this->getContainer()
            ->get(GraphqlExecutor::class)
            ->waitToExecute([
                "query"=> <<< QUERY
                    {
                        attribute_groups {
                            attribute_group_id
                            group_name
                            attributes {
                                attribute_id
                                attribute_code
                                attribute_name
                                type
                                is_required
                                display_on_frontend
                                sort_order
                                options {
                                    option_id: attribute_option_id
                                    option_text
                                }
                            }
                        }
                    }
QUERY

            ])
            ->then(function($result) use ($response) {
                $props = ['formId'=> self::FORM_ID, 'attribute_groups' => []];
                /**@var \GraphQL\Executor\ExecutionResult $result */
                if(!$result->errors) {
                    if (isset($result->data['attribute_groups'])) {
                        $props['attribute_groups'] = $result->data['attribute_groups'];
                    }
                    $response->addWidget(
                        'product_edit_attributes',
                        'admin_product_edit_inner_right',
                        5,
                        get_js_file_url("production/catalog/product/edit/attribute.js", true),
                        $props
                    );
                }
            });

        if($request->attributes->get('_matched_route') == 'product.edit')
            $this->getContainer()
            ->get(GraphqlExecutor::class)
            ->waitToExecute([
                "query"=> <<< QUERY
                    {
                        product_attribute_index (product_id: {$request->get('id', 0)} language:{$request->get('language', get_default_language_Id())}) {
                            attribute_id
                            option_id
                            attribute_value_text
                        }
                        selected_group : product (id: {$request->get('id', 0)} language:{$request->get('language', get_default_language_Id())}) {
                            id : group_id
                        }
                    }
QUERY

            ])
            ->then(function($result) use ($response) {
                /**@var \GraphQL\Executor\ExecutionResult $result */
                if(!$result->errors) {
                    $widget = $response->getWidget("product_edit_attributes", "admin_product_edit_inner");
                    if(!$widget)
                        return;

                    if (isset($result->data['selected_group']['id']) and $result->data['selected_group']['id']) {
                        $widget['props']['selected_group'] = $result->data['selected_group']['id'];
                    }
                    if (isset($result->data['product_attribute_index']) and $result->data['product_attribute_index']) {
                        $widget['props']['product_attribute_index'] = $result->data['product_attribute_index'];
                    }

                    $response->addWidget(
                        'product_edit_attributes',
                        'admin_product_edit_inner_right',
                        5,
                        get_js_file_url("production/catalog/product/edit/attribute.js", true),
                        $widget['props']
                    );
                }
            });

        return $delegate;
    }
}
