<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Controller\Annotation\RequestParam;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpException;
use Brick\Http\Request;
use Brick\Http\Exception\HttpBadRequestException;
use Brick\Http\Exception\HttpInternalServerErrorException;

/**
 * Injects request parameters into controllers with the QueryParam and PostParam annotations.
 */
class RequestParamPlugin extends AbstractAnnotationPlugin
{
    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher) : void
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
     *
     * @throws HttpException
     */
    private function getParameters(Request $request, \ReflectionFunctionAbstract $controller) : array
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
     * @throws HttpException
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

            if ($parameter->hasType() && $parameter->allowsNull()) {
                return null;
            }

            if ($parameter->isVariadic()) {
                return [];
            }

            throw $this->missingParameterException($controller, $annotation);
        }

        $value = $requestParameters[$parameterName];

        if ($value === '' && $parameter->getClass() && $parameter->allowsNull()) {
            return null;
        }

        if ($parameter->isArray() && ! is_array($value)) {
            throw $this->invalidArrayParameterException($controller, $annotation);
        }

        if ($parameter->isArray() || $parameter->getClass()) {
            return $value;
        }

        $type = $parameter->getType();

        if ($type === null) {
            return $value;
        }

        $type = (string) $type;

        if ($value === '') {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }
        }

        if ($type === 'bool') {
            if ($value === '0' || $value === 'false' || $value === 'off' || $value === 'no') {
                return false;
            }
            if ($value === '1' || $value === 'true' || $value === 'on' || $value === 'yes') {
                return true;
            }
        } elseif ($type === 'int') {
            if (ctype_digit($value)) {
                return (int) $value;
            }
        } elseif ($type === 'float') {
            if (is_numeric($value)) {
                return (float) $value;
            }
        } elseif ($type === 'string') {
            return $value;
        } else {
            throw $this->unsupportedBuiltinType($controller, $annotation, $type);
        }

        throw $this->invalidScalarParameterException($controller, $annotation, $type);
    }

    /**
     * @param \ReflectionFunctionAbstract $controller
     * @param RequestParam                $annotation
     *
     * @return HttpInternalServerErrorException
     */
    private function unknownParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation) : HttpInternalServerErrorException
    {
        return new HttpInternalServerErrorException(sprintf(
            '%s() does not have a $%s parameter, please check your annotation.',
            $this->reflectionTools->getFunctionName($controller),
            $annotation->getBindTo()
        ));
    }

    /**
     * @param \ReflectionFunctionAbstract $controller
     * @param RequestParam                $annotation
     *
     * @return HttpBadRequestException
     */
    private function missingParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation) : HttpBadRequestException
    {
        return new HttpBadRequestException(sprintf(
            '%s() requires a %s parameter "%s" which is missing in the request.',
            $this->reflectionTools->getFunctionName($controller),
            $annotation->getParameterType(),
            $annotation->getName()
        ));
    }

    /**
     * @param \ReflectionFunctionAbstract $controller
     * @param RequestParam                $annotation
     *
     * @return HttpBadRequestException
     */
    private function invalidArrayParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation) : HttpBadRequestException
    {
        return new HttpBadRequestException(sprintf(
            '%s() expects an array for %s parameter "%s" (bound to $%s), string given.',
            $this->reflectionTools->getFunctionName($controller),
            $annotation->getParameterType(),
            $annotation->getName(),
            $annotation->getBindTo()
        ));
    }

    /**
     * @param \ReflectionFunctionAbstract $controller
     * @param RequestParam                $annotation
     * @param string                      $type
     *
     * @return HttpBadRequestException
     */
    private function invalidScalarParameterException(\ReflectionFunctionAbstract $controller, RequestParam $annotation, string $type) : HttpBadRequestException
    {
        return new HttpBadRequestException(sprintf(
            '%s() received an invalid %s value for %s parameter "%s" (bound to $%s).',
            $this->reflectionTools->getFunctionName($controller),
            $type,
            $annotation->getParameterType(),
            $annotation->getName(),
            $annotation->getBindTo()
        ));
    }

    /**
     * @param \ReflectionFunctionAbstract $controller
     * @param RequestParam                $annotation
     * @param string                      $type
     *
     * @return HttpInternalServerErrorException
     */
    private function unsupportedBuiltinType(\ReflectionFunctionAbstract $controller, RequestParam $annotation, string $type) : HttpInternalServerErrorException
    {
        return new HttpInternalServerErrorException(sprintf(
            '%s() requests an unsupported type (%s) for %s parameter "%s" (bound to $%s).',
            $this->reflectionTools->getFunctionName($controller),
            $type,
            $annotation->getParameterType(),
            $annotation->getName(),
            $annotation->getBindTo()
        ));
    }
}
