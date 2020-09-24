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
    private string $name;

    /**
     * The variable to bind to, or null if same as $name.
     */
    private string|null $bindTo;

    public function __construct(string $name, string|null $bindTo = null)
    {
        $this->name   = $name;
        $this->bindTo = $bindTo;
    }

    /**
     * Returns the query or post parameter name.
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Returns the variable to bind to.
     */
    public function getBindTo() : string
    {
        return $this->bindTo ?? $this->name;
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
