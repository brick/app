<?php

namespace Brick\App;

/**
 * Exception thrown when an invalid controller has been returned by a router.
 */
class RoutingException extends \RuntimeException
{
    /**
     * @param \ReflectionException $e
     * @param string               $class
     * @param string               $method
     *
     * @return RoutingException
     */
    public static function invalidControllerClassMethod(\ReflectionException $e, string $class, string $method) : RoutingException
    {
        return new self(sprintf(
            'Cannot find a controller method called %s::%s().',
            $class,
            $method
        ), 0, $e);
    }

    /**
     * @param \ReflectionException $e
     * @param mixed                $function
     *
     * @return RoutingException
     */
    public static function invalidControllerFunction(\ReflectionException $e, $function) : RoutingException
    {
        return new self(sprintf(
            'Invalid controller function: function name or closure expected, %s given.',
            is_string($function) ? '"' . $function . '"' : gettype($function)
        ), 0, $e);
    }
}
