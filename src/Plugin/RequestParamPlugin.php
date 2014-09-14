<?php

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Plugin;
use Brick\App\Controller\Annotation\RequestParam;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpNotFoundException;
use Brick\Http\Request;
use Brick\Http\Exception\HttpBadRequestException;
use Brick\Http\Exception\HttpInternalServerErrorException;
use Brick\ObjectConverter\Exception\ObjectNotConvertibleException;
use Brick\ObjectConverter\Exception\ObjectNotFoundException;
use Brick\ObjectConverter\ObjectConverter;
use Brick\Reflection\ImportResolver;

/**
 * Injects request parameters into controllers with the QueryParam and PostParam annotations.
 */
class RequestParamPlugin extends AbstractAnnotationPlugin
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
            $event->addParameters($this->getParameters(
                $event->getRequest(),
                $event->getRouteMatch()->getControllerReflection()
            ));
        });
    }

    /**
     * @param \Brick\Http\Request         $request
     * @param \ReflectionFunctionAbstract $controller
     *
     * @return array
     */
    private function getParameters(Request $request, \ReflectionFunctionAbstract $controller)
    {
        if ($controller instanceof \ReflectionMethod) {
            $annotations = $this->annotationReader->getMethodAnnotations($controller);
        } else {
            // @todo annotation reading on generic functions is not available yet
            return [];
        }

        $parameters = [];
        foreach ($controller->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        $result = [];

        foreach ($annotations as $annotation) {
            if ($annotation instanceof RequestParam) {
                $result[$annotation->getBindTo()] = $this->getParameter(
                    $annotation,
                    $controller,
                    $parameters,
                    $request
                );
            }
        }

        return $result;
    }

    /**
     * @param RequestParam                $annotation The annotation.
     * @param \ReflectionFunctionAbstract $controller The reflection of the controller function.
     * @param \ReflectionParameter[]      $parameters An array of ReflectionParameter for the function, indexed by name.
     * @param Request                     $request    The HTTP Request.
     *
     * @return mixed The value to assign to the function parameter.
     *
     * @throws \RuntimeException
     * @throws \Brick\Http\Exception\HttpBadRequestException
     * @throws \Brick\Http\Exception\HttpNotFoundException
     * @throws \Brick\Http\Exception\HttpInternalServerErrorException
     */
    private function getParameter(RequestParam $annotation, \ReflectionFunctionAbstract $controller, array $parameters, Request $request)
    {
        $requestParameters = $annotation->getRequestParameters($request);
        $parameterName = $annotation->getName();
        $bindTo = $annotation->getBindTo();

        if (! isset($parameters[$bindTo])) {
            throw $this->unknownParameterException($controller, $annotation);
        }

        $parameter = $parameters[$bindTo];

        if (! isset($requestParameters[$parameterName])) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw $this->missingParameterException($controller, $annotation);
        }

        $value = $requestParameters[$parameterName];

        if ($parameter->isArray() && ! is_array($value)) {
            throw $this->invalidArrayParameterException($controller, $annotation);
        }

        $class = $parameter->getClass();

        if ($class) {
            return $this->getObject($class->getName(), $value, $annotation->getOptions());
        }

        if ($parameter->isArray()) {
            $types = $this->reflectionTools->getParameterTypes($parameter);

            // Must be a single type.
            if (count($types) === 1) {
                $type = $types[0];

                // Must end with empty square brackets.
                if (substr($type, -2) === '[]') {
                    // Remove the trailing square brackets.
                    $type = substr($type, 0, -2);

                    // Resolve the type to its fully qualified name.
                    $resolver = new ImportResolver($parameter);
                    $type = $resolver->resolve($type);

                    foreach ($value as & $item) {
                        $item = $this->getObject($type, $item, $annotation->getOptions());
                    }
                }
            }
        }

        return $value;
    }

    /**
     * @param string       $className The class name.
     * @param string|array $value     The parameter value.
     * @param array        $options   The options passed to the annotation.
     *
     * @return object
     *
     * @throws \RuntimeException
     */
    private function getObject($className, $value, array $options)
    {
        foreach ($this->objectConverters as $converter) {
            try {
                $object = $converter->expand($className, $value, $options);
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

    /**
     * @param \ReflectionFunctionAbstract                           $controller
     * @param \Brick\App\Controller\Annotation\RequestParam $annotation
     *
     * @return \Brick\Http\Exception\HttpInternalServerErrorException
     */
    private function unknownParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation)
    {
        return new HttpInternalServerErrorException(sprintf(
            '%s() does not have a $%s parameter, please check your annotation.',
            $this->reflectionTools->getFunctionName($controller),
            $annotation->getBindTo()
        ));
    }

    /**
     * @param \ReflectionFunctionAbstract               $controller
     * @param \Brick\App\Controller\Annotation\RequestParam $annotation
     *
     * @return \Brick\Http\Exception\HttpBadRequestException
     */
    private function missingParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation)
    {
        return new HttpBadRequestException(sprintf(
            '%s() requires a %s parameter "%s" which is missing in the request.',
            $this->reflectionTools->getFunctionName($controller),
            $annotation->getParameterType(),
            $annotation->getName()
        ));
    }

    /**
     * @param \ReflectionFunctionAbstract                           $controller
     * @param \Brick\App\Controller\Annotation\RequestParam $annotation
     *
     * @return \Brick\Http\Exception\HttpBadRequestException
     */
    private function invalidArrayParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation)
    {
        return new HttpBadRequestException(sprintf(
            '%s() expects an array for %s parameter "%s" (bound to $%s), string given.',
            $this->reflectionTools->getFunctionName($controller),
            $annotation->getParameterType(),
            $annotation->getName(),
            $annotation->getBindTo()
        ));
    }
}
