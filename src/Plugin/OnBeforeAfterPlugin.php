<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Plugin;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpInternalServerErrorException;
use Brick\Http\Request;
use Brick\Http\Response;
use Brick\Reflection\ReflectionTools;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Registers functions to be called before and after controller method invocation.
 */
class OnBeforeAfterPlugin implements Plugin
{
    /**
     * @var callable[][]
     */
    private array $onBefore = [];

    /**
     * @var callable[][]
     */
    private array $onAfter = [];

    private ReflectionTools $reflectionTools;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->reflectionTools = new ReflectionTools();
    }

    /**
     * @param string   $controllerClass The controller class or interface name.
     * @param callable $function        The function to invoke before the controller method.
     */
    public function onBefore(string $controllerClass, callable $function) : void
    {
        $this->onBefore[$controllerClass][] = $function;
    }

    /**
     * @param string   $controllerClass The controller class or interface name.
     * @param callable $function        The function to invoke after the controller method.
     */
    public function onAfter(string $controllerClass, callable $function) : void
    {
        $this->onAfter[$controllerClass][] = $function;
    }

    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(ControllerReadyEvent::class, function (ControllerReadyEvent $event) {
            $controller = $event->getControllerInstance();

            if ($controller === null) {
                return;
            }

            foreach ($this->onBefore as $class => $functions) {
                if ($controller instanceof $class) {
                    foreach ($functions as $function) {
                        $parameters = $this->getFunctionParameters('onBefore', $function, [
                            $class         => $controller,
                            Request::class => $event->getRequest()
                        ]);

                        $result = $function(...$parameters);

                        if ($result instanceof Response) {
                            $event->setResponse($result);
                        }
                    }
                }
            }
        });

        $dispatcher->addListener(ResponseReceivedEvent::class, function (ResponseReceivedEvent $event) {
            $controller = $event->getControllerInstance();

            if ($controller === null) {
                return;
            }

            foreach ($this->onAfter as $class => $functions) {
                if ($controller instanceof $class) {
                    foreach ($functions as $function) {
                        $parameters = $this->getFunctionParameters('onAfter', $function, [
                            $class          => $controller,
                            Request::class  => $event->getRequest(),
                            Response::class => $event->getResponse()
                        ]);

                        $function(...$parameters);
                    }
                }
            }
        });
    }

    /**
     * Resolves the parameters to call the given function.
     *
     * @param string   $onEvent  'onBefore' or 'onAfter'.
     * @param callable $function The function to resolve.
     * @param object[] $objects  An associative array of available objects, indexed by their class or interface name.
     *
     * @throws HttpInternalServerErrorException If the function requires a parameter that is not available.
     */
    private function getFunctionParameters(string $onEvent, callable $function, array $objects) : array
    {
        $parameters = [];

        $reflectionFunction = $this->reflectionTools->getReflectionFunction($function);

        foreach ($reflectionFunction->getParameters() as $reflectionParameter) {
            $parameterType = $reflectionParameter->getType();

            if ($parameterType instanceof ReflectionNamedType && ! $parameterType->isBuiltin()) {
                $parameterClassName = $parameterType->getName();

                if (isset($objects[$parameterClassName])) {
                    $parameters[] = $objects[$parameterClassName];

                    continue;
                }
            }

            throw $this->cannotResolveParameter($onEvent, array_keys($objects), $reflectionParameter);
        }

        return $parameters;
    }

    private function cannotResolveParameter(string $onEvent, array $types, ReflectionParameter $parameter) : HttpInternalServerErrorException
    {
        $message = 'Cannot resolve ' . $onEvent . ' function parameter $' . $parameter->getName() . ': ';

        $parameterType = $parameter->getType();

        if ($parameterType === null) {
            $message .= 'parameter is not typed.';
        } elseif ($parameterType instanceof ReflectionNamedType) {
            $message .= 'type ' . $parameterType->getName() . ' is not in available types (' . implode(', ', $types) . ').';
        }

        return new HttpInternalServerErrorException($message);
    }
}
