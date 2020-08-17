<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Checkout\Middleware\Checkout\ContactInfo;


use Polavi\Middleware\MiddlewareAbstract;
use Polavi\Module\Checkout\Services\Cart\Cart;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;

class AddContactInfoMiddleware extends MiddlewareAbstract
{
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        $this->getContainer()->get(Cart::class)
            ->setData('customer_full_name', $request->request->get('full_name'));
        $promise = $this->getContainer()->get(Cart::class)
            ->setData('customer_email', $request->request->get('email'));

        $promise->then(function ($value) use ($response) {
            $response->addData('success', 1);
            $response->notNewPage();
        }, function ($reason) use ($response) {
            $response->addData('success', 0)->addData('message', $reason);
            $response->addAlert("checkout_contact_info", "error", "Something wrong. Please try again.");
            $response->notNewPage();
        });

        return $delegate;
    }
}