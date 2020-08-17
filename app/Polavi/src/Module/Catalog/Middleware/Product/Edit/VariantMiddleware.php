<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Catalog\Middleware\Product\Edit;

use function Polavi\_mysql;
use function Polavi\get_js_file_url;
use Polavi\Module\Graphql\Services\GraphqlExecutor;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;
use Polavi\Middleware\MiddlewareAbstract;

class VariantMiddleware extends MiddlewareAbstract
{
    /**
     * @param Request $request
     * @param Response $response
     * @param null $delegate
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if ($response->hasWidget('product_edit_variant'))
            return $delegate;

//        // Loading data by using GraphQL
        if ($request->attributes->get('_matched_route') == 'product.edit') {
            $this->getContainer()
                ->get(GraphqlExecutor::class)
                ->waitToExecute([
                    "query"=>"{variants: product(id: {$request->get('id')}){
                        variant_group_id
                        sku
                        variants {
                            variant_product_id : product_id
                            image {
                                url: image
                                path
                            }
                            sku: sku
                            price
                            qty
                            status
                            visibility
                            attributes {
                                attribute_id
                                attribute_code
                                option_id
                                value_text : attribute_value_text
                            }
                            editUrl
                        }
                    }}"
                ])->then(function ($result) use (&$fields, $response) {
                    /**@var \GraphQL\Executor\ExecutionResult $result */
                    if (isset($result->data['variants']) and $result->data['variants']) {
                        $conn = _mysql();
                        $group = $conn->getTable("variant_group")->load($result->data['variants']['variant_group_id']);
                        if ($group) {
                            $attributes = $conn->getTable("attribute")->where("attribute_id", "IN", [
                                $group["attribute_one"],
                                $group["attribute_two"],
                                $group["attribute_three"],
                                $group["attribute_four"],
                                $group["attribute_five"],
                            ])->fetchAllAssoc();
                            $response->addWidget(
                                'product_edit_variant',
                                'admin_product_edit_inner_left',
                                50,
                                get_js_file_url("production/catalog/product/edit/variant.js", true),
                                [
                                    "id" => "product_edit_variant",
                                    "variant_group_id" => $group["variant_group_id"],
                                    "attributes" => array_map(function ($a) { return $a["attribute_id"];}, $attributes),
                                    "variants" => array_map(function ($v) use ($result) {
                                        if ($v["sku"] == $result->data["variants"]["sku"])
                                            $v["current"] = true;

                                        return $v;
                                    }, $result->data['variants']['variants'])
                                ]
                            );
                        } else {
                            $response->addWidget(
                                'product_edit_variant',
                                'admin_product_edit_inner_left',
                                50,
                                get_js_file_url("production/catalog/product/edit/variant.js", true),
                                [
                                    "id" => "product_edit_variant"
                                ]
                            );
                        }
                    } else {
                        $response->addWidget(
                            'product_edit_variant',
                            'admin_product_edit_inner_left',
                            50,
                            get_js_file_url("production/catalog/product/edit/variant.js", true),
                            [
                                "id"=>"product_edit_variant"
                            ]
                        );
                    }
                }, function ($reason) { var_dump($reason);});

        } else
            $response->addWidget(
                'product_edit_variant',
                'admin_product_edit_inner_left',
                50,
                get_js_file_url("production/catalog/product/edit/variant.js", true),
                [
                    "id"=>"product_edit_variant"
                ]
            );

        return $delegate;
    }
}
