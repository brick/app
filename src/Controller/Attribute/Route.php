<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Attribute;
use LogicException;
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
     * Available options:
     *
     * - 'patterns': a map of parameter name to regexp patterns
     * - 'methods': a list of HTTP methods this route is valid for
     */
    public function __construct(string $path, array $options = [])
    {
        $this->path = $path;

        if (isset($options['patterns'])) {
            $this->checkStringArrayOption('patterns', $options['patterns']);
            $this->patterns = $options['patterns'];
            unset($options['patterns']);
        }

        if (isset($options['methods'])) {
            $this->checkStringArrayOption('methods', $options['methods']);
            $this->methods = $options['methods'];
            unset($options['methods']);
        }

        foreach ($options as $option) {
            throw new LogicException(sprintf("Unknown option '%s'.", $option));
        }
    }

    private function checkStringArrayOption(string $name, mixed $value) : void
    {
        if (! is_array($value)) {
            throw new TypeError(sprintf(
                "Option '%s' must be of type array, %s given.",
                $name,
                gettype($value)
            ));
        }

        foreach ($value as $item) {
            if (! is_string($item)) {
                throw new TypeError(sprintf(
                    "Option '%s' must only contain strings, %s given.",
                    $name,
                    gettype($item)
                ));
            }
        }
    }
}
