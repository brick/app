<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Brick\Http\Request;

/**
 * Base class for QueryParam and PostParam.
 */
abstract class RequestParam
{
    /**
     * The query or post parameter name.
     */
    public string $name;

    /**
     * The variable name to bind to.
     */
    public string $bindTo;

    public function __construct(string $name, string|null $bindTo = null)
    {
        $this->name   = $name;
        $this->bindTo = $bindTo ?? $name;
    }

    /**
     * Returns the request parameter type: query or post.
     */
    abstract public function getParameterType() : string;

    /**
     * Returns the relevant query/post parameters from the request.
     */
    abstract public function getRequestParameters(Request $request) : array;
}
