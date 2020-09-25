<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Controller\Attribute\RequestParam;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpException;
use Brick\Http\Request;
use Brick\Http\Exception\HttpBadRequestException;
use Brick\Http\Exception\HttpInternalServerErrorException;
use ReflectionAttribute;
use ReflectionFunctionAbstract;

/**
 * Injects request parameters into controllers using the QueryParam and PostParam attributes.
 */
class RequestParamPlugin extends AbstractAttributePlugin
{
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
     * @throws HttpException
     */
    private function getParameters(Request $request, ReflectionFunctionAbstract $controller) : array
    {
        /** @var RequestParam[] $requestParamAttributes */
        $requestParamAttributes = $controller->getAttributes(RequestParam::class, ReflectionAttribute::IS_INSTANCEOF);

        $parameters = [];
        foreach ($controller->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }

        $result = [];

        foreach ($requestParamAttributes as $attribute) {
            $result[$attribute->bindTo] = $this->getParameter(
                $attribute,
                $controller,
                $parameters,
                $request
            );
        }

        return $result;
    }

    /**
     * @param RequestParam                $attribute  The attribute.
     * @param \ReflectionFunctionAbstract $controller The reflection of the controller function.
     * @param \ReflectionParameter[]      $parameters An array of ReflectionParameter for the function, indexed by name.
     * @param Request                     $request    The HTTP Request.
     *
     * @return mixed The value to assign to the function parameter.
     *
     * @throws HttpException
     */
    private function getParameter(RequestParam $attribute, ReflectionFunctionAbstract $controller, array $parameters, Request $request) : mixed
    {
        $requestParameters = $attribute->getRequestParameters($request);
        $parameterName = $attribute->name;
        $bindTo = $attribute->bindTo;

        if (! isset($parameters[$bindTo])) {
            throw $this->unknownParameterException($controller, $attribute);
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

            throw $this->missingParameterException($controller, $attribute);
        }

        $value = $requestParameters[$parameterName];

        if ($value === '' && $parameter->getClass() && $parameter->allowsNull()) {
            return null;
        }

        if ($parameter->isArray() && ! is_array($value)) {
            throw $this->invalidArrayParameterException($controller, $attribute);
        }

        if ($parameter->isArray() || $parameter->getClass()) {
            return $value;
        }

        $type = $parameter->getType();

        if ($type === null) {
            return $value;
        }

        $type = $type->getName();

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
            throw $this->unsupportedBuiltinType($controller, $attribute, $type);
        }

        throw $this->invalidScalarParameterException($controller, $attribute, $type);
    }

    private function unknownParameterException(ReflectionFunctionAbstract $controller, RequestParam $attribute) : HttpInternalServerErrorException
    {
        return new HttpInternalServerErrorException(sprintf(
            '%s() does not have a $%s parameter, please check your attribute.',
            $this->reflectionTools->getFunctionName($controller),
            $attribute->bindTo
        ));
    }

    private function missingParameterException(ReflectionFunctionAbstract $controller, RequestParam $attribute) : HttpBadRequestException
    {
        return new HttpBadRequestException(sprintf(
            '%s() requires a %s parameter "%s" which is missing in the request.',
            $this->reflectionTools->getFunctionName($controller),
            $attribute->getParameterType(),
            $attribute->name
        ));
    }

    private function invalidArrayParameterException(ReflectionFunctionAbstract $controller, RequestParam $attribute) : HttpBadRequestException
    {
        return new HttpBadRequestException(sprintf(
            '%s() expects an array for %s parameter "%s" (bound to $%s), string given.',
            $this->reflectionTools->getFunctionName($controller),
            $attribute->getParameterType(),
            $attribute->name,
            $attribute->bindTo
        ));
    }

    private function invalidScalarParameterException(ReflectionFunctionAbstract $controller, RequestParam $attribute, string $type) : HttpBadRequestException
    {
        return new HttpBadRequestException(sprintf(
            '%s() received an invalid %s value for %s parameter "%s" (bound to $%s).',
            $this->reflectionTools->getFunctionName($controller),
            $type,
            $attribute->getParameterType(),
            $attribute->name,
            $attribute->bindTo
        ));
    }

    private function unsupportedBuiltinType(ReflectionFunctionAbstract $controller, RequestParam $attribute, string $type) : HttpInternalServerErrorException
    {
        return new HttpInternalServerErrorException(sprintf(
            '%s() requests an unsupported type (%s) for %s parameter "%s" (bound to $%s).',
            $this->reflectionTools->getFunctionName($controller),
            $type,
            $attribute->getParameterType(),
            $attribute->name,
            $attribute->bindTo
        ));
    }
}
