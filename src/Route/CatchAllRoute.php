<?php

namespace Brick\App\Route;

use Brick\Http\Request;
use Brick\App\Route;
use Brick\App\RouteMatch;

class CatchAllRoute implements Route
{
    /**
     * @var \Brick\App\RouteMatch
     */
    private $routeMatch;

    /**
     * @param RouteMatch $routeMatch
     */
    public function __construct(RouteMatch $routeMatch)
    {
        $this->routeMatch = $routeMatch;
    }

    /**
     * {@inheritdoc}
     */
    public function match(Request $request) : ?RouteMatch
    {
        return $this->routeMatch;
    }
}
