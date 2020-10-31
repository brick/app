<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Request;
use Brick\App\RouteMatch;

/**
 * Event dispatched after controller invocation, regardless of whether an exception was thrown.
 */
final class ControllerInvocatedEvent
{
    /**
     * The request.
     *
     * @var Request
     */
    private Request $request;

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
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
     */
    public function __construct(Request $request, RouteMatch $routeMatch, ?object $instance)
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
}
