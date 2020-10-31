<?php

declare(strict_types=1);

namespace Brick\App\Route;

use Brick\Http\Request;
use Brick\App\Route;
use Brick\App\RouteMatch;

class CatchAllRoute implements Route
{
    /**
     * @var RouteMatch
     */
    private RouteMatch $routeMatch;

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
