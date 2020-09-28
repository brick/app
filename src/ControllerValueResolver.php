<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\Http\Request;
use Brick\DI\ValueResolver;
use Brick\Reflection\ReflectionTools;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Resolves controller values in the application.
 */
class ControllerValueResolver implements ValueResolver
{
    private ValueResolver $fallbackResolver;

    private Request|null $request = null;

    private ReflectionTools $reflectionTools;

    public function __construct(ValueResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
        $this->reflectionTools  = new ReflectionTools();
    }

    /**
     * Sets the request being processed.
     */
    public function setRequest(Request $request) : void
    {
        $this->request = $request;
    }

    public function getParameterValue(ReflectionParameter $parameter) : mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->allowsNull() && $type->getName() === Request::class) {
            return $this->request;
        }

        return $this->fallbackResolver->getParameterValue($parameter);
    }

    public function getPropertyValue(ReflectionProperty $property) : mixed
    {
        $type = $property->getType();

        if ($type instanceof ReflectionNamedType && ! $type->allowsNull() && $type->getName() === Request::class) {
            return $this->request;
        }

        return $this->fallbackResolver->getPropertyValue($property);
    }
}
