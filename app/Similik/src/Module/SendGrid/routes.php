<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@similik.com>.
 * See COPYING.txt for license details.
 */


declare(strict_types=1);

/** @var \Similik\Services\Routing\Router $router */

$router->addAdminRoute('setting.sendgrid', ["POST", "GET"], '/setting/sendgrid', [
    \Similik\Module\SendGrid\Middleware\Setting\FormMiddleware::class,
    \Similik\Module\SendGrid\Middleware\Setting\SaveMiddleware::class
]);
