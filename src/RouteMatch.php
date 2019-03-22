<?php

declare(strict_types=1);

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
    private $classParameters = [];

    /**
     * @var array
     */
    private $functionParameters = [];

    /**
     * @param \ReflectionFunctionAbstract $controller         A reflection of the controller.
     * @param array                       $classParameters    An associative array of parameters matched from the request to resolve the controller class constructor parameters.
     * @param array                       $functionParameters An associative array of parameters matched from the request to resolve the controller function parameters.
     */
    public function __construct(\ReflectionFunctionAbstract $controller, array $classParameters = [], array $functionParameters = [])
    {
        $this->controller         = $controller;
        $this->classParameters    = $classParameters;
        $this->functionParameters = $functionParameters;
    }

    /**
     * Returns a RouteMatch for the given class and method.
     *
     * @param string $class
     * @param string $method
     * @param array  $classParameters
     * @param array  $methodParameters
     *
     * @return RouteMatch The route match.
     *
     * @throws RoutingException If the class or method does not exist.
     */
    public static function forMethod(string $class, string $method, array $classParameters = [], array $methodParameters = []) : RouteMatch
    {
        try {
            $controller = new \ReflectionMethod($class, $method);
        } catch (\ReflectionException $e) {
            throw RoutingException::invalidControllerClassMethod($e, $class, $method);
        }

        return new RouteMatch($controller, $classParameters, $methodParameters);
    }

    /**
     * Returns a RouteMatch for the given function or closure.
     *
     * @param string|\Closure $function
     * @param array           $functionParameters
     *
     * @return RouteMatch The route match.
     *
     * @throws RoutingException If the function is invalid.
     */
    public static function forFunction($function, array $functionParameters = []) : RouteMatch
    {
        try {
            $controller = new \ReflectionFunction($function);
        } catch (\ReflectionException $e) {
            throw RoutingException::invalidControllerFunction($e, $function);
        }

        return new self($controller, [], $functionParameters);
    }

    /**
     * @return \ReflectionFunctionAbstract
     */
    public function getControllerReflection() : \ReflectionFunctionAbstract
    {
        return $this->controller;
    }

    /**
     * @return array
     */
    public function getClassParameters() : array
    {
        return $this->classParameters;
    }

    /**
     * @return array
     */
    public function getFunctionParameters() : array
    {
        return $this->functionParameters;
    }

    /**
     * Returns a copy of this RouteMatch, with additional class parameters.
     *
     * Parameters with the same name will override current parameters.
     * This RouteMatch instance is immutable, and unaffected by this method call.
     *
     * @param array $parameters An associative array of class parameters.
     *
     * @return RouteMatch
     */
    public function withClassParameters(array $parameters) : RouteMatch
    {
        $routeMatch = clone $this;
        $routeMatch->classParameters = $parameters + $this->classParameters;

        return $routeMatch;
    }

    /**
     * Returns a copy of this RouteMatch, with additional function parameters.
     *
     * Parameters with the same name will override current parameters.
     * This RouteMatch instance is immutable, and unaffected by this method call.
     *
     * @param array $parameters An associative array of function parameters.
     *
     * @return RouteMatch
     */
    public function withFunctionParameters(array $parameters) : RouteMatch
    {
        $routeMatch = clone $this;
        $routeMatch->functionParameters = $parameters + $this->functionParameters;

        return $routeMatch;
    }
}
