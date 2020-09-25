<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\Http\Exception\HttpException;
use Brick\Http\Request;

/**
 * Matches a request to a controller.
 */
interface Route
{
    /**
     * Attempts to match the given request to a controller.
     *
     * @param Request $request The request to match.
     *
     * @return RouteMatch|null A match, or null if no match is found.
     *
     * @throws HttpException A route is allowed to throw HTTP exceptions.
     */
    public function match(Request $request) : RouteMatch|null;
}
