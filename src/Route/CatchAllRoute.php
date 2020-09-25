<?php

declare(strict_types=1);

namespace Brick\App\Route;

use Brick\Http\Request;
use Brick\App\Route;
use Brick\App\RouteMatch;

class CatchAllRoute implements Route
{
    private RouteMatch $routeMatch;

    public function __construct(RouteMatch $routeMatch)
    {
        $this->routeMatch = $routeMatch;
    }

    public function match(Request $request) : RouteMatch|null
    {
        return $this->routeMatch;
    }
}
