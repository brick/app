<?php

namespace Brick\App;

use Brick\Http\Request;

/**
 * Matches a request to a controller.
 */
interface Route
{
    /**
     * Attemps to match the given request to a controller.
     *
     * @param \Brick\Http\Request $request The request to match.
     *
     * @return \Brick\App\RouteMatch|null A match, or null if no match is found.
     *
     * @throws \Brick\Http\Exception\HttpException A route is allowed to throw HTTP exceptions.
     */
    public function match(Request $request) : ?RouteMatch;
}
