<?php

declare(strict_types=1);

namespace Brick\App\Route;

use Brick\App\Route;
use Brick\App\RouteMatch;
use Brick\Http\Request;

/**
 * A simple route implementation, where each path directory is mapped to a controller class.
 *
 * Each path folder must start and end with a slash.
 *
 * Example:
 *
 * new SimpleRoute([
 *     '/'      => 'App\Controller\IndexController',
 *     '/user/' => 'App\Controller\UserController',
 * ]);
 */
class SimpleRoute implements Route
{
    private array $routes;

    /**
     * SimpleRoute constructor.
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function match(Request $request) : RouteMatch|null
    {
        $path = $request->getPath();

        if ($path === '') {
            return null;
        }

        if ($path[0] !== '/') {
            return null;
        }

        $lastSlashPos = strrpos($path, '/');
        $prefix = substr($path, 0, $lastSlashPos + 1);
        $action = substr($path, $lastSlashPos + 1);

        if (! isset($this->routes[$prefix])) {
            return null;
        }

        $class = $this->routes[$prefix];

        if ($action === 'index') {
            return null;
        }

        if ($action === '') {
            $action = 'index';
        }

        $method = $this->capitalize($action) . 'Action';

        $classParameters = $this->getClassParameters($request);

        if ($classParameters === null) {
            return null;
        }

        $functionParameters = $this->getFunctionParameters($request);

        if ($functionParameters === null) {
            return null;
        }

        return RouteMatch::forMethod($class, $method, $classParameters, $functionParameters);
    }

    /**
     * Returns parameters to pass to the controller class, or NULL to skip this route.
     *
     * This is designed to be extended.
     */
    protected function getClassParameters(Request $request) : array|null
    {
        return [];
    }

    /**
     * Returns parameters to pass to the controller function, or NULL to skip this route.
     *
     * This is designed to be extended.
     */
    protected function getFunctionParameters(Request $request) : array|null
    {
        return [];
    }

    /**
     * Capitalizes a dashed string, e.g. foo-bar => fooBar.
     */
    private function capitalize(string $name) : string
    {
        return preg_replace_callback('/\-([a-z])/', fn(array $matches) => strtoupper($matches[1]), $name);
    }
}
