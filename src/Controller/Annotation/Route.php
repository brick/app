<?php

declare(strict_types=1);

namespace Brick\App\Controller\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;

/**
 * Defines a route on a controller.
 *
 * When used on a class, it defines a prefix for the routes of all class methods, and named parameters will be provided
 * as constructor parameters.
 *
 * When used on a function, named parameters will be provided as function parameters.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
final class Route
{
    /**
     * The path, with optional {named} parameters.
     *
     * @Required
     *
     * @var string
     */
    public $path;

    /**
     * A map of parameter name to regexp patterns.
     *
     * The pattern defaults to [^\/]+, but can be overridden here.
     * NO CAPTURING PARENTHESES MUST BE USED INSIDE THESE PATTERNS.
     *
     * @var string[]
     */
    public $patterns = [];

    /**
     * The list of HTTP methods (e.g. GET or POST) this route is valid for.
     *
     * If this list is empty, all methods are allowed.
     * Note that this property is only valid on controller methods, and ignored on controller classes.
     *
     * @var string[]
     */
    public $methods = [];
}
