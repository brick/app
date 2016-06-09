<?php

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
    /**
     * @var array
     */
    private $routes;

    /**
     * SimpleRoute constructor.
     *
     * @param array $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Request $request)
    {
        $path = $request->getPath();

        if ($path == '') {
            return null;
        }

        if ($path[0] != '/') {
            return null;
        }

        $lastSlashPos = strrpos($path, '/');
        $prefix = substr($path, 0, $lastSlashPos + 1);
        $action = substr($path, $lastSlashPos + 1);

        if (! isset($this->routes[$prefix])) {
            return null;
        }

        $class = $this->routes[$prefix];

        if ($action == 'index') {
            return null;
        } elseif ($action == '') {
            $action = 'index';
        }

        $method = $this->capitalize($action) . 'Action';

        return RouteMatch::forMethod($class, $method);
    }

    /**
     * Capitalizes a dashed string, e.g. foo-bar => fooBar.
     *
     * @param string $name
     *
     * @return string
     */
    private function capitalize(string $name)
    {
        return preg_replace_callback('/\-([a-z])/', function (array $matches) {
            return strtoupper($matches[1]);
        }, $name);
    }
}
