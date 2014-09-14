<?php

namespace Brick\App\Event;

use Brick\Http\Request;
use Brick\App\RouteMatch;

/**
 * Event dispatched when the controller is ready to be invoked.
 *
 * If the controller is a class method, the class will have been instantiated
 * and this controller instance is made available to the event.
 */
final class ControllerReadyEvent
{
    /**
     * The request.
     *
     * @var Request
     */
    private $request;

    /**
     * The route match.
     *
     * @var \Brick\App\RouteMatch
     */
    private $routeMatch;

    /**
     * The controller instance, or null if the controller is not a class method.
     *
     * @var object|null
     */
    private $instance;

    /**
     * An associative array of parameters to resolve the controller arguments.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * @param Request     $request    The request.
     * @param \Brick\App\RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
     */
    public function __construct(Request $request, RouteMatch $routeMatch, $instance)
    {
        $this->request    = $request;
        $this->routeMatch = $routeMatch;
        $this->instance   = $instance;
    }

    /**
     * Returns the request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the route match.
     *
     * @return \Brick\App\RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->routeMatch;
    }

    /**
     * Returns the controller instance, or null if the controller is not a class method.
     *
     * @return object|null
     */
    public function getControllerInstance()
    {
        return $this->instance;
    }

    /**
     * Adds parameters to resolve the controller arguments.
     *
     * @param array $parameters An associative array of key-value pairs.
     *
     * @return void
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     * Returns the parameters to resolve the controller arguments.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
