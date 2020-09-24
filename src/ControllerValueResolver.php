<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\Http\Request;
use Brick\DI\ValueResolver;
use Brick\Reflection\ReflectionTools;

/**
 * Resolves controller values in the application.
 */
class ControllerValueResolver implements ValueResolver
{
    /**
     * @var \Brick\DI\ValueResolver
     */
    private $fallbackResolver;

    /**
     * @var \Brick\Http\Request|null
     */
    private $request = null;

    /**
     * @var \Brick\Reflection\ReflectionTools
     */
    private $reflectionTools;

    /**
     * @param \Brick\DI\ValueResolver $fallbackResolver
     */
    public function __construct(ValueResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
        $this->reflectionTools  = new ReflectionTools();
    }

    /**
     * Sets the request being processed.
     *
     * @param \Brick\Http\Request $request
     *
     * @return void
     */
    public function setRequest(Request $request) : void
    {
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterValue(\ReflectionParameter $parameter) : mixed
    {
        $class = $parameter->getClass();
        if ($class && $class->getName() === Request::class) {
            return $this->request;
        }

        return $this->fallbackResolver->getParameterValue($parameter);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValue(\ReflectionProperty $property) : mixed
    {
        $class = $this->reflectionTools->getPropertyClass($property);
        if ($class === Request::class) {
            return $this->request;
        }

        return $this->fallbackResolver->getPropertyValue($property);
    }
}
