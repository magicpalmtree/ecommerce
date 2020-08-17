<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Customer\Middleware\Create;


use function Polavi\dispatch_event;
use Polavi\Middleware\MiddlewareAbstract;
use Polavi\Module\Graphql\Services\GraphqlExecutor;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;
use Polavi\Services\Routing\Router;

class CreateAccountMiddleware extends MiddlewareAbstract
{

    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        if ($request->getCustomer()->isLoggedIn()) {
            $response->redirect($this->getContainer()->get(Router::class)->generateUrl('homepage'));
            return $response;
        }

        $variables = $request->get('variables', []);
        $query = "mutation CreateCustomer(\$customer: CustomerInput!) { createCustomer (customer: \$customer) {status message customer {customer_id group_id status full_name email}}}";
        dispatch_event("filter_create_customer_query", [&$query]);
        $response->notNewPage();
        $promise = $this->getContainer()
            ->get(GraphqlExecutor::class)
            ->waitToExecute([
                "query" => $query,
                "variables" => $variables
            ]);

        $promise->then(function ($result) use ($request, $response) {
            $response->addData('customerCreation', $result->data['createCustomer']);
        });

        $promise->otherwise(function ($reason) use ($request, $response) {
            $response->addData('customerCreation', ['status'=> false, 'message'=> "Internal server error"]);
        });

        return $promise;
    }
}