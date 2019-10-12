<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Similik\Module\Customer\Middleware\Login;


use function Similik\get_js_file_url;
use Similik\Middleware\MiddlewareAbstract;
use Similik\Services\Http\Request;
use Similik\Services\Http\Response;
use Similik\Services\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;

class FormMiddleware extends MiddlewareAbstract
{
    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if($request->getCustomer()->isLoggedIn()) {
            $response->redirect($this->getContainer()->get(Router::class)->generateUrl('homepage'));
            return $response;
        } else {
            $response->addWidget(
                'customer_registration_form',
                'content',
                10,
                get_js_file_url("production/customer/login_form.js", false),
                [
                    'action' => $this->getContainer()->get(Router::class)->generateUrl('customer.auth'),
                    'registerUrl' => $this->getContainer()->get(Router::class)->generateUrl('customer.register')
                ]
            );

            return $delegate;
        }
    }
}