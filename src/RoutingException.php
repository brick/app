<?php

declare(strict_types=1);

namespace Brick\App;

use ReflectionException;

/**
 * Exception thrown when an invalid controller has been returned by a router.
 */
class RoutingException extends \RuntimeException
{
    public static function invalidControllerClassMethod(ReflectionException $e, string $class, string $method) : RoutingException
    {
        return new self(sprintf(
            'Cannot find a controller method called %s::%s().',
            $class,
            $method
        ), 0, $e);
    }

    public static function invalidControllerFunction(ReflectionException $e, mixed $function) : RoutingException
    {
        return new self(sprintf(
            'Invalid controller function: function name or closure expected, %s given.',
            is_string($function) ? '"' . $function . '"' : get_debug_type($function)
        ), 0, $e);
    }
}
