<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Middleware;

use function Polavi\get_js_file_url;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;

class AdminLayoutMiddleware extends MiddlewareAbstract
{
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if (!$request->isAdmin()) {
            return $delegate;
        }
        if (!$response->isNewPage()) {
            return $delegate;
        }

        $response->addWidget(
            'container',
            'wrapper',
            0,
            get_js_file_url("production/area.js"),
            [
                "id" => "container",
                "className" => "container-fluid " . str_replace(".", "-", $request->attributes->get("_matched_route"))
            ]
        );

        $response->addWidget(
            'admin_menu',
            'container',
            10,
            get_js_file_url("production/area.js", true),
            ["id"=> "menu", "className" => "admin-navigation"]
        );

        $response->addWidget(
            'admin_content',
            'container',
            20,
            get_js_file_url("production/area.js", true),
            [
                "id"=> "content_wrapper",
                "className"=> "content-wrapper"
            ]
        );

        $response->addWidget(
            'admin_header',
            'content_wrapper',
            0,
            get_js_file_url("production/area.js", true),
            ["id"=> "header", "className" => "header"]
        );

        $response->addWidget(
            'dashboard_title',
            'content',
            0,
            get_js_file_url("production/cms/title.js", true)
        );

        $response->addWidget(
            'admin_content_inner',
            'content_wrapper',
            10,
            get_js_file_url("production/area.js", true),
            ["id"=> "content", "className" => "content"]
        );

        $response->addWidget(
            'admin_footer',
            'content_wrapper',
            20,
            get_js_file_url("production/cms/footer.js", true)
        );

        return $delegate;
    }
}