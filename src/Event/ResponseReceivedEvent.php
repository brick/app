<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Request;
use Brick\Http\Response;
use Brick\App\RouteMatch;

/**
 * Event dispatched after the controller response has been received.
 *
 * If an HttpException is caught during the controller method invocation,
 * the exception it is converted to a Response, and this event is dispatched as well.
 *
 * Other exceptions break the application flow and don't trigger this event.
 */
final class ResponseReceivedEvent
{
    /**
     * The request.
     */
    private Request $request;

    /**
     * The response.
     */
    private Response $response;

    /**
     * The route match.
     */
    private RouteMatch $routeMatch;

    /**
     * The controller instance, or null if the controller is not a class method.
     */
    private object|null $instance;

    /**
     * @param Request     $request    The request.
     * @param Response    $response   The response.
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
     */
    public function __construct(Request $request, Response $response, RouteMatch $routeMatch, object|null $instance)
    {
        $this->request    = $request;
        $this->response   = $response;
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
     * Returns the response.
     */
    public function getResponse() : Response
    {
        return $this->response;
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
}
