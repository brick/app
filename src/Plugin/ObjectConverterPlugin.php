<?php

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Plugin;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpException;
use Brick\Http\Exception\HttpNotFoundException;
use Brick\Http\Exception\HttpBadRequestException;
use Brick\Http\Exception\HttpInternalServerErrorException;
use Brick\ObjectConverter\Exception\ObjectNotConvertibleException;
use Brick\ObjectConverter\Exception\ObjectNotFoundException;
use Brick\ObjectConverter\ObjectConverter;

/**
 * Automatically converts type-hinted objects in controller parameters.
 *
 * The original parameter values can come from routers or other plugins.
 * When using parameters from plugins such as RequestParamPlugin, these plugins must be registered before this one.
 */
class ObjectConverterPlugin extends AbstractAnnotationPlugin
{
    /**
     * @var \Brick\ObjectConverter\ObjectConverter[]
     */
    private $objectConverters = [];

    /**
     * @param ObjectConverter $converter
     *
     * @return static
     */
    public function addObjectConverter(ObjectConverter $converter)
    {
        $this->objectConverters[] = $converter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->addListener(ControllerReadyEvent::class, function(ControllerReadyEvent $event) {
            $event->addParameters($this->getParameters($event));
        });
    }

    /**
     * @param ControllerReadyEvent $event
     *
     * @return array The value to assign to the function parameter.
     *
     * @throws HttpException If the object cannot be instantiated.
     */
    private function getParameters(ControllerReadyEvent $event)
    {
        $controller = $event->getRouteMatch()->getControllerReflection();

        $currentParameters = $event->getParameters();
        $newParameters = [];

        foreach ($controller->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (isset($currentParameters[$name])) {
                $newParameters[$name] = $this->getParameterValue($parameter, $currentParameters[$name]);
            }
        }

        return $newParameters;
    }

    /**
     * @param \ReflectionParameter $parameter
     * @param mixed                $value
     *
     * @return mixed
     *
     * @throws HttpException If the object cannot be instantiated.
     */
    private function getParameterValue(\ReflectionParameter $parameter, $value)
    {
        $class = $parameter->getClass();

        if ($class) {
            if ($parameter->isVariadic()) {
                $result = [];

                foreach ($value as $subValue) {
                    $result[] = $this->getObject($class->getName(), $subValue);
                }

                return $result;
            } else {
                return $this->getObject($class->getName(), $value);
            }
        }

        return $value;
    }

    /**
     * @param string $className The resulting object class name.
     * @param mixed  $value     The raw parameter value to convert to an object.
     *
     * @return object
     *
     * @throws HttpException If the object cannot be instantiated.
     */
    private function getObject($className, $value)
    {
        foreach ($this->objectConverters as $converter) {
            try {
                $object = $converter->expand($className, $value);
            }
            catch (ObjectNotConvertibleException $e) {
                throw new HttpBadRequestException($e->getMessage(), $e);
            }
            catch (ObjectNotFoundException $e) {
                throw new HttpNotFoundException($e->getMessage(), $e);
            }

            if ($object) {
                return $object;
            }
        }

        throw new HttpInternalServerErrorException('No object converter available for ' . $className);
    }
}
