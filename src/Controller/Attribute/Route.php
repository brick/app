<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Attribute;
use TypeError;

/**
 * Defines a route on a controller.
 *
 * When used on a class, it defines a prefix for the routes of all class methods, and named parameters will be provided
 * as constructor parameters.
 *
 * When used on a function, named parameters will be provided as function parameters.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final class Route
{
    /**
     * The path, with optional {named} parameters.
     */
    public string $path;

    /**
     * A map of parameter name to regexp patterns.
     *
     * The pattern defaults to [^\/]+, but can be overridden here.
     * NO CAPTURING PARENTHESES MUST BE USED INSIDE THESE PATTERNS.
     *
     * @var string[]
     */
    public array $patterns;

    /**
     * The list of HTTP methods (e.g. GET or POST) this route is valid for.
     *
     * If this list is empty, all methods are allowed.
     * Note that this property is only valid on controller methods, and ignored on controller classes.
     *
     * @var string[]
     */
    public array $methods;

    /**
     * The route priority, in case multiple routes match the same path / method.
     *
     * Route with the highest priority wins. Default priority is zero.
     *
     * @var int|null
     */
    public ?int $priority;

    /**
     * @param string   $path     The path, with optional {named} parameters.
     * @param array    $patterns A map of parameter name to regexp patterns.
     * @param array    $methods  The list of HTTP methods this route is valid for.
     * @param int|null $priority The route priority, in case multiple routes match. Highest priority wins.
     */
    public function __construct(string $path, array $patterns = [], array $methods = [], ?int $priority = null)
    {
        $this->checkStringArray('patterns', $patterns);
        $this->checkStringArray('methods', $methods);

        $this->path     = $path;
        $this->patterns = $patterns;
        $this->methods  = $methods;
        $this->priority = $priority;
    }

    private function checkStringArray(string $name, array $values) : void
    {
        foreach ($values as $value) {
            if (! is_string($value)) {
                throw new TypeError(sprintf(
                    'Parameter $%s must only contain strings, %s given.',
                    $name,
                    gettype($value)
                ));
            }
        }
    }
}
