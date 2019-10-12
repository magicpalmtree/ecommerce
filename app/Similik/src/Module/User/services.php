<?php
/**
 * Copyright © Nguyen Huu The <thenguyen.dev@gmail.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

/**@var \Similik\Services\Di\Container $container */

$container[\Similik\Module\User\Services\Authenticator::class] = function() use ($container) {
    return new \Similik\Module\User\Services\Authenticator($container[\Similik\Services\Http\Request::class]);
};