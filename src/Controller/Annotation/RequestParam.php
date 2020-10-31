<?php

declare(strict_types=1);

namespace Brick\App\Controller\Annotation;

use Brick\Http\Request;

/**
 * Base class for QueryParam and PostParam.
 */
abstract class RequestParam extends AbstractAnnotation
{
    /**
     * The query or post parameter name.
     *
     * @var string
     */
    private string $name;

    /**
     * The variable to bind to, or null if same as $name.
     *
     * @var string|null
     */
    private ?string $bindTo;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->name   = $this->getRequiredString($values, 'name', true);
        $this->bindTo = $this->getOptionalString($values, 'bindTo');
    }

    /**
     * Returns the query or post parameter name.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Returns the variable to bind to.
     *
     * @return string
     */
    public function getBindTo() : string
    {
        return $this->bindTo ?? $this->name;
    }

    /**
     * Returns the request parameter type: query or post.
     *
     * @return string
     */
    abstract public function getParameterType() : string;

    /**
     * Returns the relevant query/post parameters from the request.
     *
     * @param \Brick\Http\Request $request
     *
     * @return array
     */
    abstract public function getRequestParameters(Request $request) : array;
}
