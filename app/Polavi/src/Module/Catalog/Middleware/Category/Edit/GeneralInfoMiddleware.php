<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Catalog\Middleware\Category\Edit;

use function Polavi\get_js_file_url;
use Polavi\Module\Graphql\Services\GraphqlExecutor;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;
use Polavi\Middleware\MiddlewareAbstract;

class GeneralInfoMiddleware extends MiddlewareAbstract
{

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if ($response->hasWidget('category_edit_general'))
            return $delegate;

        // Loading data by using GraphQL
        if ($request->attributes->get('_matched_route') == 'category.edit')
            $this->getContainer()
            ->get(GraphqlExecutor::class)
            ->waitToExecute([
                "query"=>"{generalInfo: category(id: {$request->get('id')}){name status description include_in_nav position}}"
            ])
            ->then(function ($result) use ($response) {
                /**@var \GraphQL\Executor\ExecutionResult $result */
                if (isset($result->data['generalInfo'])) {
                    $response->addWidget(
                        'category_edit_general',
                        'admin_category_edit_inner_left',
                        10,
                        get_js_file_url("production/catalog/category/edit/general.js", true),
                        ["id"=>"category_edit_general", "data" => $result->data['generalInfo']]
                    );
                }
            });
        else
            $response->addWidget(
                'category_edit_general',
                'admin_category_edit_inner_left',
                10,
                get_js_file_url("production/catalog/category/edit/general.js", true),
                ["id"=>"category_edit_general", "data" => []]
            );
        return $delegate;
    }
}