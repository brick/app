<?php

namespace Brick\App;

use Brick\Http\Request;
use Brick\Di\ValueResolver;
use Brick\Reflection\ReflectionTools;

/**
 * Resolves controller values in the application.
 */
class ControllerValueResolver implements ValueResolver
{
    /**
     * @var \Brick\Di\ValueResolver
     */
    private $fallbackResolver;

    /**
     * @var \Brick\Http\Request|null
     */
    private $request = null;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var \Brick\Reflection\ReflectionTools
     */
    private $reflectionTools;

    /**
     * @param \Brick\Di\ValueResolver $fallbackResolver
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
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Sets key-value pairs to resolve the controller parameters.
     *
     * @param array $parameters
     *
     * @return void
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Adds key-value pairs to resolve the controller parameters.
     *
     * The parameters will be merged with the current parameters.
     * If duplicate keys are found, new keys overwrite previous ones.
     *
     * @param array $parameters
     *
     * @return void
     */
    public function addParameters(array $parameters)
    {
        $this->parameters = $parameters + $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterValue(\ReflectionParameter $parameter)
    {
        $class = $parameter->getClass();
        if ($class && $class->getName() == Request::class) {
            return $this->request;
        }

        $name = $parameter->getName();
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }

        return $this->fallbackResolver->getParameterValue($parameter);
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyValue(\ReflectionProperty $property)
    {
        $class = $this->reflectionTools->getPropertyClass($property);
        if ($class == Request::class) {
            return $this->request;
        }

        $name = $property->getName();
        if (isset($this->parameters[$name])) {
            return $this->parameters[$name];
        }

        return $this->fallbackResolver->getPropertyValue($property);
    }
}
