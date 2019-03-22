<?php

declare(strict_types=1);

namespace Brick\App\Controller\Annotation;

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
class Route
{
    /**
     * @var string
     */
    private $regexp;

    /**
     * The list of parameter names.
     *
     * @var string[]
     */
    private $parameterNames = [];

    /**
     * A map of parameter name to regexp patterns.
     *
     * The pattern defaults to [^\/]+, but can be overridden here.
     * NO CAPTURING PARENTHESES MUST BE USED INSIDE THESE PATTERNS.
     *
     * @var string[]
     */
    private $patterns = [];

    /**
     * The list of HTTP methods (e.g. GET or POST) this route is valid for.
     *
     * If this list is empty, all methods are allowed.
     * Note that this property is only valid on controller methods, and ignored on controller classes.
     *
     * @var string[]
     */
    private $methods = [];

    /**
     * @param array $params
     */
    public function __construct(array $params)
    {
        if (! isset($params['value'])) {
            throw new \LogicException('@Route requires a path.');
        }

        $path = $params['value'];

        if (! is_string($path)) {
            throw new \LogicException('@Route path must be a string.');
        }

        if (isset($params['methods'])) {
            if (! is_array($params['methods'])) {
                throw new \LogicException('@Route.methods must be an array of strings.');
            }

            foreach ($params['methods'] as $method) {
                if (! is_string($method)) {
                    throw new \LogicException('@Route.methods must only contain strings.');
                }
            }

            $this->methods = $params['methods'];
        }

        if (isset($params['patterns'])) {
            if (! is_array($params['patterns'])) {
                throw new \LogicException('@Route.patterns must be an array of strings.');
            }

            foreach ($params['patterns'] as $pattern) {
                if (! is_string($pattern)) {
                    throw new \LogicException('@Route.patterns must only contain strings.');
                }
            }

            $this->patterns = $params['patterns'];
        }

        $this->regexp = preg_replace_callback('/\{([^\}]+)\}|(.+?)/', function(array $matches) : string {
            if (isset($matches[2])) {
                return preg_quote($matches[2], '/');
            }

            $parameterName = $matches[1];
            $this->parameterNames[] = $parameterName;

            if (isset($this->patterns[$parameterName])) {
                $pattern = $this->patterns[$parameterName];
            } else {
                $pattern = '[^\/]+';
            }

            return '(' . $pattern. ')';
        }, $path);

        foreach ($this->patterns as $parameterName => $pattern) {
            if (! in_array($parameterName, $this->parameterNames, true)) {
                throw new \LogicException(sprintf('Pattern does not match any parameter: "%s".', $parameterName));
            }
        }
    }

    /**
     * @return string
     */
    public function getRegexp() : string
    {
        return $this->regexp;
    }

    /**
     * @return string[]
     */
    public function getParameterNames() : array
    {
        return $this->parameterNames;
    }

    /**
     * @return string[]
     */
    public function getMethods() : array
    {
        return $this->methods;
    }
}
