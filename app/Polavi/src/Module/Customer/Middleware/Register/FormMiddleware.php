<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Customer\Middleware\Register;


use function Polavi\get_base_url;
use function Polavi\get_js_file_url;
use Polavi\Middleware\MiddlewareAbstract;
use Polavi\Services\Helmet;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;
use Polavi\Services\Routing\Router;

class FormMiddleware extends MiddlewareAbstract
{

    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if ($request->getCustomer()->isLoggedIn() == true) {
            $response->redirect($this->getContainer()->get(Router::class)->generateUrl('homepage'));
            return $response;
        }

        $this->getContainer()->get(Helmet::class)->setTitle("Register for an account");

        $response->addWidget(
            'customer_registration_form',
            'content_center',
            10,
            get_js_file_url("production/customer/registration_form.js", false),
            [
                'action' => $this->getContainer()->get(Router::class)->generateUrl('customer.register.post'),
                'redirectUrl' => get_base_url(false)
            ]
        );

        return $delegate;
    }
}