<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Module\Catalog\Middleware\Product\View;

use function Similik\_mysql;
use function Similik\get_default_language_Id;
use Similik\Services\Helmet;
use Similik\Services\Http\Request;
use Similik\Services\Http\Response;
use Similik\Middleware\MiddlewareAbstract;


class InitMiddleware extends MiddlewareAbstract
{
    /**
     * @param Request $request
     * @param Response $response
     * @param null $delegate
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if($request->attributes->get('slug'))
            $product = _mysql()->getTable('product')
            ->leftJoin('product_description', null, [
                [
                    'column'      => "product_description.language_id",
                    'operator'    => "=",
                    'value'       => $request->get('language', get_default_language_Id()),
                    'ao'          => 'and',
                    'start_group' => null,
                    'end_group'   => null
                ]
            ])
            ->where('product_description.seo_key', '=', $request->attributes->get('slug'))
            ->fetchOneAssoc();
        else
            $product = _mysql()->getTable('product')
                ->leftJoin('product_description', null, [
                    [
                        'column'      => "product_description.language_id",
                        'operator'    => "=",
                        'value'       => $request->get('language', get_default_language_Id()),
                        'ao'          => 'and',
                        'start_group' => null,
                        'end_group'   => null
                    ]
                ])
                ->where('product.product_id', '=', $request->attributes->get('id'))
                ->fetchOneAssoc();

        if(!$product)
            $response->setStatusCode(404);
        else {
            $request->attributes->set('id', $product['product_id']);
            $this->getContainer()->get(Helmet::class)->setTitle($product['name'])->addMeta([
                'name'=> 'description',
                'content' => $product['short_description']
            ]);
        }

        return $delegate;
    }
}