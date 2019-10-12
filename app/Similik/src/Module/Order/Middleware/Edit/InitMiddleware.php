<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Module\Order\Middleware\Edit;

use function Similik\_mysql;
use Similik\Services\Helmet;
use Similik\Services\Http\Response;
use Similik\Services\Http\Request;
use Similik\Middleware\MiddlewareAbstract;

class InitMiddleware extends MiddlewareAbstract
{
    /**
     * @param Request $request
     * @param Response $response
     * @param null $delegate
     * @return mixed
     * @internal param callable $next
     */
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        $id = (int) $request->attributes->get('id');
        $order = _mysql()->getTable('order')->load($id);
        if($order === false) {
            $response->addData('success', 0);
            $response->addData('message', 'Requested order does not exist');

            return $response;
        }
        $this->getContainer()->get(Helmet::class)->setTitle("Order #{$order['order_number']}");

        return $delegate;
    }
}