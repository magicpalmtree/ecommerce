<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Middleware;


use function Similik\get_js_file_url;
use Similik\Services\Http\Request;
use Similik\Services\Http\Response;

class FrontLayoutMiddleware extends MiddlewareAbstract
{
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if($request->isAdmin())
            return $delegate;
        if($request->getMethod() != 'GET')
            return $delegate;
        if(
            $request->attributes->get('_matched_route') == 'graphql.api'
        )
            return $delegate;
        $response->addWidget(
            'header',
            'container',
            0,
            get_js_file_url("production/area.js"),
            ["id"=> "header", "className" => "uk-width-1-1"]
        );

        $response->addWidget(
            'menu',
            'container',
            10,
            get_js_file_url("production/area.js"),
            ["id"=> "menu", "className" => "uk-width-1-1"]
        );
        $response->addWidget(
            'content_grid',
            'container',
            20,
            get_js_file_url("production/area.js"),
            ["id"=> "content_grid", "className" => "uk-grid uk-grid-small"]
        );
        $response->addWidget(
            'leftColumn',
            'content_grid',
            11,
            get_js_file_url("production/area.js"),
            ["id"=> "leftColumn", "className"=> "uk-width-1-6"]
        );
        $response->addWidget(
            'rightColumn',
            'content_grid',
            1000,
            get_js_file_url("production/area.js"),
            ["id"=> "rightColumn", "className"=> "uk-width-1-6"]
        );
        $response->addWidget(
            'content',
            'content_grid',
            20,
            get_js_file_url("production/cms/page/content_layout.js", false)
        );

        return $delegate;
    }
}