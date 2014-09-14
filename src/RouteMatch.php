<?php

namespace Brick\App;

/**
 * Represents a match between a route and a request as returned by Route::match().
 */
class RouteMatch
{
    /**
     * @var \ReflectionFunctionAbstract
     */
    private $controller;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param \ReflectionFunctionAbstract $controller A reflection of the controller.
     * @param array                       $parameters An associative array of parameters matched from the request.
     */
    public function __construct(\ReflectionFunctionAbstract $controller, array $parameters = [])
    {
        $this->controller = $controller;
        $this->parameters = $parameters;
    }

    /**
     * Returns a RouteMatch for the given class and method.
     *
     * @param string $class
     * @param string $method
     * @param array  $parameters
     *
     * @return RouteMatch The route match.
     *
     * @throws RoutingException If the class or method does not exist.
     */
    public static function forMethod($class, $method, array $parameters = [])
    {
        try {
            return new self(new \ReflectionMethod($class, $method), $parameters);
        } catch (\ReflectionException $e) {
            throw RoutingException::invalidControllerClassMethod($e, $class, $method);
        }
    }

    /**
     * Returns a RouteMatch for the given function or closure.
     *
     * @param string|\Closure $function
     * @param array           $parameters
     *
     * @return RouteMatch The route match.
     *
     * @throws RoutingException If the function is invalid.
     */
    public static function forFunction($function, array $parameters = [])
    {
        try {
            return new self(new \ReflectionFunction($function), $parameters);
        } catch (\ReflectionException $e) {
            throw RoutingException::invalidControllerFunction($e, $function);
        }
    }

    /**
     * @return \ReflectionFunctionAbstract
     */
    public function getControllerReflection()
    {
        return $this->controller;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
