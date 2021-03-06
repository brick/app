<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\ObjectPacker\PackedObject;
use Brick\App\Plugin;
use Brick\App\ObjectPacker\Exception\ObjectNotConvertibleException;
use Brick\App\ObjectPacker\Exception\ObjectNotFoundException;
use Brick\App\ObjectPacker\ObjectPacker;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpException;
use Brick\Http\Exception\HttpNotFoundException;
use Brick\Http\Exception\HttpBadRequestException;
use Brick\Http\Exception\HttpInternalServerErrorException;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Automatically converts type-hinted objects in controller parameters, from their string or array representation.
 *
 * The original parameter values can come from routers or other plugins.
 * When using parameters from plugins such as RequestParamPlugin, these plugins must be registered before this one.
 */
class ObjectUnpackPlugin implements Plugin
{
    private ObjectPacker $objectPacker;

    public function __construct(ObjectPacker $objectPacker)
    {
        $this->objectPacker = $objectPacker;
    }

    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(ControllerReadyEvent::class, function(ControllerReadyEvent $event) {
            $event->addParameters($this->getParameters($event));
        });
    }

    /**
     * @return array The value to assign to the function parameter.
     *
     * @throws HttpException If the object cannot be instantiated.
     */
    private function getParameters(ControllerReadyEvent $event) : array
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
     * @throws HttpException If the object cannot be instantiated.
     */
    private function getParameterValue(ReflectionParameter $parameter, mixed $value) : mixed
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $className = $type->getName();

            if ($parameter->isVariadic()) {
                $result = [];

                foreach ($value as $subValue) {
                    $result[] = $this->getObject($className, $subValue);
                }

                return $result;
            }

            return $this->getObject($className, $value);
        }

        return $value;
    }

    /**
     * @param string $className The resulting object class name.
     * @param mixed  $value     The raw parameter value to convert to an object.
     *
     * @throws HttpException If the object cannot be instantiated.
     */
    private function getObject(string $className, mixed $value) : object
    {
        $packedObject = new PackedObject($className, $value);

        try {
            $object = $this->objectPacker->unpack($packedObject);
        }
        catch (ObjectNotConvertibleException $e) {
            throw new HttpBadRequestException($e->getMessage(), $e);
        }
        catch (ObjectNotFoundException $e) {
            throw new HttpNotFoundException($e->getMessage(), $e);
        }

        if ($object === null) {
            throw new HttpInternalServerErrorException('No object packer available for ' . $className);
        }

        return $object;
    }
}
