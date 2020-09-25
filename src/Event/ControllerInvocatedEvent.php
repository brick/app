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
     * @param Request     $request    The request.
     * @param RouteMatch  $routeMatch The route match.
     * @param object|null $instance   The controller instance.
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
}
