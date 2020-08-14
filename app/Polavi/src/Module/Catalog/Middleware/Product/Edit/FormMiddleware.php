<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Catalog\Middleware\Product\Edit;

use function Polavi\generate_url;
use function Polavi\get_js_file_url;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;
use Polavi\Middleware\MiddlewareAbstract;
use Polavi\Services\Routing\Router;

class FormMiddleware extends MiddlewareAbstract
{
    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        $response->addWidget(
            'product-edit-form',
            'content',
            10,
            get_js_file_url("production/catalog/product/edit/product_edit_form.js", true),
            [
                "id"=> 'product-edit-form',
                "action" => $this->getContainer()->get(Router::class)->generateUrl("product.save", ['id'=>$request->attributes->get('id', null)], $request->query->get('language', null) != null ? ['language' => $request->query->get('language')] : null),
                "listUrl" => generate_url('product.grid'),
                "cancelUrl" => $request->attributes->get('id') ? generate_url('product.edit', ['id' => $request->attributes->get('id')]) : generate_url('product.create')
            ]
        );

        return $delegate;
    }
}
