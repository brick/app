<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Request;
use Brick\Http\Response;
use Brick\App\RouteMatch;

/**
 * Event dispatched if the controller does not return a Response object.
 *
 * This event provides an opportunity for plugins to transform an arbitrary controller result
 * into a Response object. For example, it could be used to JSON-encode the controller return value
 * and wrap it into a Response object with the proper Content-Type.
 */
final class NonResponseResultEvent
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
     * The controller return value.
     */
    private mixed $result;

    /**
     * The response provided by a listener, or null if no response was provided.
     */
    private Response|null $response = null;

    /**
     * @param Request     $request    The request.
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
     * @param mixed       $result     The controller result.
     */
    public function __construct(Request $request, RouteMatch $routeMatch, object|null $instance, mixed $result)
    {
        $this->request    = $request;
        $this->routeMatch = $routeMatch;
        $this->instance   = $instance;
        $this->result     = $result;
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
     * Returns the value returned by the controller.
     */
    public function getControllerResult() : mixed
    {
        return $this->result;
    }

    /**
     * Sets the response.
     */
    public function setResponse(Response $response) : void
    {
        $this->response = $response;
    }

    /**
     * Returns the response provided by a listener, or null if no response was provided.
     */
    public function getResponse() : Response|null
    {
        return $this->response;
    }
}
