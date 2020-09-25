<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Request;
use Brick\App\RouteMatch;

/**
 * Event dispatched after the router has returned a match.
 */
final class RouteMatchedEvent
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
     * @param Request    $request    The request.
     * @param RouteMatch $routeMatch The route match.
     */
    public function __construct(Request $request, RouteMatch $routeMatch)
    {
        $this->request    = $request;
        $this->routeMatch = $routeMatch;
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
}
