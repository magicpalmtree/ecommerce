<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Module\Order\Middleware\Dashboard;


use function Polavi\_mysql;
use function Polavi\generate_url;
use function Polavi\get_js_file_url;
use Polavi\Middleware\MiddlewareAbstract;
use Polavi\Module\Graphql\Services\GraphqlExecutor;
use Polavi\Services\Db\Processor;
use Polavi\Services\Http\Request;
use Polavi\Services\Http\Response;

class BestCustomersMiddleware extends MiddlewareAbstract
{

    public function __invoke(Request $request, Response $response, $delegate = null)
    {
        $conn = $this->getContainer()->get(Processor::class);
        $customers = $conn->executeQuery("SELECT `customer`.customer_id, `customer`.full_name, COUNT(`order`.order_id) as orders, SUM(`order`.grand_total) as `total`
        FROM `customer`
        INNER JOIN `order`
        ON `customer`.customer_id = `order`.customer_id
        GROUP BY `customer`.customer_id
        ORDER BY `orders` DESC
        LIMIT 0, 10
        ")->fetchAll(\PDO::FETCH_ASSOC);

        array_walk($customers, function (&$c) {
            $c["editUrl"] = generate_url("customer.edit", ["id"=> $c['customer_id']]);
        });

        $response->addWidget(
            'best_customers',
            'admin_dashboard_middle_right',
            30,
            get_js_file_url("production/order/dashboard/best_customers.js", true),
            [
                'customers' => $customers,
                'listUrl' => generate_url("customer.grid")
            ]
        );

        return $delegate;
    }
}