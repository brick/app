<?php

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
     * @return RouteMatch
     */
    public function getRouteMatch()
    {
        return $this->routeMatch;
    }
}
