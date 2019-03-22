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
     * @var string[]
     */
    private $parameterNames = [];

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

        $this->regexp = preg_replace_callback('/\{([^\}]+)\}|(.+?)/', function(array $matches) : string {
            if (isset($matches[2])) {
                return preg_quote($matches[2], '/');
            }

            $this->parameterNames[] = $matches[1];

            return '([^\/]+)';
        }, $path);
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
}
