<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Request;
use Brick\Http\Response;
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
     */
    private Request $request;

    /**
     * The route match.
     */
    private RouteMatch $routeMatch;

    /**
     * The controller instance, or null if the controller is not a class method.
     */
    private object|null $instance;

    /**
     * An associative array of parameters to resolve the controller arguments.
     */
    private array $parameters = [];

    /**
     * An early response to return when a plugin decides to short-circuit the normal application flow.
     */
    private Response|null $response = null;

    /**
     * @param Request     $request    The request.
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance, if any.
     */
    public function __construct(Request $request, RouteMatch $routeMatch, object|null $instance)
    {
        $this->request    = $request;
        $this->routeMatch = $routeMatch;
        $this->instance   = $instance;
    }

    /**
     * Returns the request.
     */
    public function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * Returns the route match.
     */
    public function getRouteMatch() : RouteMatch
    {
        return $this->routeMatch;
    }

    /**
     * Returns the controller instance, or null if the controller is not a class method.
     */
    public function getControllerInstance() : object|null
    {
        return $this->instance;
    }

    /**
     * Adds parameters to resolve the controller arguments.
     *
     * @param array $parameters An associative array of key-value pairs.
     */
    public function addParameters(array $parameters) : void
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     * Returns the parameters to resolve the controller arguments.
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function setResponse(Response|null $response = null) : void
    {
        $this->response = $response;
    }

    public function getResponse() : Response|null
    {
        return $this->response;
    }
}
