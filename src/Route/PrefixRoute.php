<?php

declare(strict_types=1);

namespace Brick\App\Route;

use Brick\App\RouteMatch;
use Brick\Http\Request;
use Brick\App\Route;

/**
 * Conditionally forwards the routing to a given Route if the request matches a given prefix.
 * Example: `/admin/`. Note that the leading `/` is required.
 */
class PrefixRoute implements Route
{
    /**
     * The route to forward to.
     */
    private Route $route;

    /**
     * The prefixes to match.
     */
    private array $prefixes;

    /**
     * @param Route $route    The route to forward to.
     * @param array $prefixes The prefixes to match.
     */
    public function __construct(Route $route, array $prefixes)
    {
        $this->route = $route;
        $this->prefixes = $prefixes;
    }

    public function match(Request $request) : RouteMatch|null
    {
        $path = $request->getPath();

        foreach ($this->prefixes as $prefix) {
            if (strpos($path, $prefix) === 0) {
                return $this->route->match($request);
            }
        }

        return null;
    }
}
