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
     *
     * @var Request
     */
    private Request $request;

    /**
     * The response.
     *
     * @var Response
     */
    private Response $response;

    /**
     * The route match.
     *
     * @var RouteMatch
     */
    private RouteMatch $routeMatch;

    /**
     * The controller instance, or null if the controller is not a class method.
     *
     * @var object|null
     */
    private ?object $instance;

    /**
     * @param Request     $request    The request.
     * @param Response    $response   The response.
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
     */
    public function __construct(Request $request, Response $response, RouteMatch $routeMatch, ?object $instance)
    {
        $this->request    = $request;
        $this->response   = $response;
        $this->routeMatch = $routeMatch;
        $this->instance   = $instance;
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
     * Returns the response.
     *
     * @return Response
     */
    public function getResponse() : Response
    {
        return $this->response;
    }

    /**
     * Updates the response.
     */
    public function setResponse(Response $response): void
    {
        $this->response = $response;
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
}
