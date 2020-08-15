<?php
/**
 * Copyright © Nguyen Huu The <the.nguyen@polavi.com>.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Polavi\Services\Routing;

use FastRoute;
use function Polavi\get_base_url;
use Polavi\Services\Http\Request;
use Polavi\Services\Routing\RouteParser as RouteParser;

class Router
{
    /** @var Request  */
    private $request;

    /** @var RouteParser  */
    private $parser;

    private $adminRoutes = [];

    private $siteRoutes = [];

    public function __construct(Request $request, RouteParser $parser)
    {
        $this->request = $request;
        $this->parser = $parser;
    }

    /**
     * @param string $id
     * @param $method
     * @param string $pattern
     * @param array $middleware
     * @return $this
     */
    public function addAdminRoute(string $id, $method, string $pattern, array $middleware)
    {
        if (isset($this->adminRoutes[$id])) {
            throw new \Error("{$id} route already existed");
        }
        $this->adminRoutes[$id] = [
            $method,
            $pattern == '/'? '/' . ADMIN_PATH : '/' . ADMIN_PATH . $pattern,
            $middleware
        ];

        return $this;
    }

    /**
     * @param string $id
     * @param $method
     * @param string $pattern
     * @param array $middleware
     * @return $this
     */
    public function addSiteRoute(string $id, $method, string $pattern, array $middleware)
    {
        if (isset($this->siteRoutes[$id])) {
            throw new \Error("{$id} route already existed");
        }
        $this->siteRoutes[$id] = [
            $method,
            $pattern,
            $middleware
        ];

        return $this;
    }

    /**
     * @return FastRoute\Dispatcher
     */
    protected function routeInit()
    {
        if ($this->request->isAdmin()) {
            $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
                foreach ($this->adminRoutes as $name => $route) {
                    $r->addRoute($route[0], $route[1], $name);
                }
            });
        } else {
            $dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
                foreach ($this->siteRoutes as $name => $route) {
                    $r->addRoute($route[0], $route[1], $name);
                }
            });
        }

        return $dispatcher;
    }

    /**
     * @return int
     */
    public function dispatch()
    {
        $dispatcher = $this->routeInit();
        $routeInfo = $dispatcher->dispatch($this->request->getMethod(), $this->request->getPathInfo());

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                $this->request->attributes->set('_matched_route', 'not.found');
                return 404;
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                return 405;
                break;
            case FastRoute\Dispatcher::FOUND:
                foreach ($routeInfo[2] as $key => $val) {
                    $this->request->attributes->set($key, $val);
                }
                $this->request->attributes->set('_matched_route', $routeInfo[1]);
                $routedMiddleware = $this->request->isAdmin() ?
                    $this->adminRoutes[$routeInfo[1]][2] : $this->siteRoutes[$routeInfo[1]][2];
                $this->request->attributes->set('_routed_middleware', $routedMiddleware);
                $this->request->attributes->set('_route_args', $routeInfo[2]);
                return 200;
                break;
            default:
                return 200;
        }
    }

    /**
     * This method generates a url base on route ID and arguments
     * @param string $routeId
     * @param array $params
     * @param array|null $query
     * @return string
     */
    public function generateUrl(string $routeId, array $params = [], array $query = null) : string
    {
        $route = $this->siteRoutes[$routeId] ?? $this->adminRoutes[$routeId] ?? null;

        if ($route == null) {
            throw new \RuntimeException(sprintf(
                'Cannot generate URI for route "%s"; route not found',
                $routeId
            ));
        }

        $routes = $this->parser->parse($route[1]);
        $path = get_base_url();
        foreach ($routes as $part) {
            if (is_string($part)) {
                // Append the string
                $path .= $part;
                continue;
            }

            if (isset($part[2]) and (!isset($params[$part[0]]) or $params[$part[0]] === null)) {
                continue;
            }

            if (! preg_match('~^' . $part[1] . '$~', (string) $params[$part[0]])) {
                throw new \LogicException(sprintf(
                    'Parameter value for [%s] did not match the regex `%s`',
                    $part[0],
                    $part[1]
                ));
            }

            $path .= $params[$part[0]];
        }

        if ($query) {
            $queryString = http_build_query($query);
            return trim($path, '/') . "?" . $queryString;
        } else {
            return trim($path, '/');
        }
    }

    /**
     * @return array
     */
    public function getSiteRoutes(): array
    {
        return $this->siteRoutes;
    }
}