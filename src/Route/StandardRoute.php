<?php

namespace Brick\App\Route;

use Brick\App\Route;
use Brick\App\RouteMatch;
use Brick\Http\Request;
use Brick\Http\Exception\HttpNotFoundException;
use Brick\Http\Exception\HttpRedirectException;

/**
 * Standard /namespace/controller/action route.
 */
class StandardRoute implements Route
{
    /**
     * The base namespace.
     *
     * @var string
     */
    private $namespace;

    /**
     * @param string $namespace The base namespace for controller classes.
     */
    public function __construct($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Request $request)
    {
        $path = $request->getPathParts();

        if (count($path) < 2) {
            while (count($path) < 2) {
                $path[] = 'index';
            }

            throw new HttpRedirectException('/' . implode('/', $path), 301);
        }

        $namespace = $this->namespace;

        foreach (array_slice($path, 0, -2) as $ns) {
            $namespace .= '\\' . $this->getNamespaceName($ns);
        }

        $class  = $this->getClassName($path[count($path) - 2]);
        $method = $this->getMethodName($path[count($path) - 1]);

        $class = $namespace . '\\' . $class;

        try {
            $reflectionClass = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new HttpNotFoundException(sprintf('Controller class "%s" not found', $class), $e);
        }

        try {
            $reflectionMethod = $reflectionClass->getMethod($method);
        } catch (\ReflectionException $e) {
            throw new HttpNotFoundException(sprintf('Controller method "%s::%s()" not found', $class, $method), $e);
        }

        // @todo compute the exact path (correct capitalization and dashes), and 301-redirect if wrong request path.

        return new RouteMatch($reflectionMethod);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getNamespaceName($name)
    {
        return ucfirst($this->capitalize($name));
    }

    /**
     * @param string $name
     * @return string
     */
    private function getClassName($name)
    {
        return ucfirst($this->capitalize($name)) . 'Controller';
    }

    /**
     * @param string $name
     * @return string
     */
    private function getMethodName($name)
    {
        return $this->capitalize($name) . 'Action';
    }

    /**
     * Capitalizes a dashed string, e.g. foo-bar => fooBar.
     *
     * @param string $name
     * @return string
     */
    private function capitalize($name)
    {
        return preg_replace_callback('/\-([a-z])/', function (array $matches) {
            return strtoupper($matches[1]);
        }, $name);
    }
}
