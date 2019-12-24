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
     *
     * @var Request
     */
    private $request;

    /**
     * The route match.
     *
     * @var RouteMatch
     */
    private $routeMatch;

    /**
     * The controller instance, or null if the controller is not a class method.
     *
     * @var object|null
     */
    private $instance;

    /**
     * The controller return value.
     *
     * @var mixed
     */
    private $result;

    /**
     * The response provided by a listener, or null if no response was provided.
     *
     * @var Response|null
     */
    private $response;

    /**
     * @param Request     $request    The request.
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
     * @param mixed       $result     The controller result.
     */
    public function __construct(Request $request, RouteMatch $routeMatch, ?object $instance, $result)
    {
        $this->request    = $request;
        $this->routeMatch = $routeMatch;
        $this->instance   = $instance;
        $this->result     = $result;
    }

    /**
     * Returns the request.
     *
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * Returns the route match.
     *
     * @return RouteMatch
     */
    public function getRouteMatch() : RouteMatch
    {
        return $this->routeMatch;
    }

    /**
     * Returns the controller instance, or null if the controller is not a class method.
     *
     * @return object|null
     */
    public function getControllerInstance() : ?object
    {
        return $this->instance;
    }

    /**
     * Returns the value returned by the controller.
     *
     * @return mixed
     */
    public function getControllerResult()
    {
        return $this->result;
    }

    /**
     * Sets the response.
     *
     * @param Response $response
     *
     * @return void
     */
    public function setResponse(Response $response) : void
    {
        $this->response = $response;
    }

    /**
     * Returns the response provided by a listener, or null if no response was provided.
     *
     * @return Response|null
     */
    public function getResponse() : ?Response
    {
        return $this->response;
    }
}
