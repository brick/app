<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\App\Controller\Attribute\Route;
use LogicException;
use ReflectionClass;
use TypeError;

/**
 * Compiles Route attributes into an array to be provided to the AttributeRoute.
 *
 * The result is a list of [regexp, httpMethods, className, methodName, classParameterNames, methodParameterNames].
 */
class AttributeRouteCompiler
{
    /**
     * @var string[]
     */
    private array $defaultMethods;

    /**
     * An array of [path, HTTP methods, controller class, controller method] arrays.
     */
    private array $routes = [];

    /**
     * AttributeRouteCompiler constructor.
     *
     * @param string[] $defaultMethods The HTTP methods allowed by default, if the attribute does not provide them.
     *                                 Defaults to ANY.
     */
    public function __construct(array $defaultMethods = [])
    {
        foreach ($defaultMethods as $method) {
            if (! is_string($method)) {
                throw new TypeError(sprintf('Default HTTP methods must be an array of string, %s found in array.', get_debug_type($method)));
            }
        }

        $this->defaultMethods   = $defaultMethods;
    }

    /**
     * @param string[] $classNames An array of controller class names to get routes from.
     *
     * @throws LogicException
     */
    public function compile(array $classNames) : array
    {
        $result = [];

        foreach ($classNames as $className) {
            $reflectionClass = new ReflectionClass($className);

            $prefixPath = '';
            $prefixRegexp = '';
            $classParameterNames = [];
            $classPriority = null;

            foreach ($reflectionClass->getAttributes(Route::class) as $attribute) {
                /** @var Route $attribute */
                $prefixPath = $attribute->path;
                $classPriority = $attribute->priority;
                [$prefixRegexp, $classParameterNames] = $this->processAttribute($attribute);

                break;
            }

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $methodName = $reflectionMethod->getName();

                foreach ($reflectionMethod->getAttributes(Route::class) as $attribute) {
                    /** @var Route $attribute */
                    [$regexp, $methodParameterNames] = $this->processAttribute($attribute);

                    $pathRegexp = '/^' . $prefixRegexp . $regexp . '$/';
                    $httpMethods = $attribute->methods;

                    if (! $httpMethods) {
                        $httpMethods = $this->defaultMethods;
                    }

                    $priority = $attribute->priority ?? $classPriority ?? 0;

                    $this->routes[] = [
                        $prefixPath . $attribute->path,
                        $attribute->methods,
                        $className,
                        $methodName
                    ];

                    $result[] = [
                        $pathRegexp,
                        $httpMethods,
                        $priority,
                        $className,
                        $methodName,
                        $classParameterNames,
                        $methodParameterNames
                    ];
                }
            }
        }

        // Sort routes by path & methods
        sort($this->routes);

        return $result;
    }

    /**
     * Returns an array of [path, HTTP methods, controller class, controller method] arrays.
     *
     * This array is only available after compile() has been executed.
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Creates a path regular expression and infer the parameter names from a Route attribute.
     *
     * @param Route $attribute The attribute to process.
     *
     * @return array The path regexp, and the parameter names.
     *
     * @throws LogicException
     */
    private function processAttribute(Route $attribute) : array
    {
        $parameterNames = [];

        $regexp = preg_replace_callback('/\{([^\}]+)\}|(.+?)/', static function(array $matches) use ($attribute, & $parameterNames) : string {
            if (isset($matches[2])) {
                return preg_quote($matches[2], '/');
            }

            $parameterName = $matches[1];
            $parameterNames[] = $parameterName;

            if (isset($attribute->patterns[$parameterName])) {
                $pattern = $attribute->patterns[$parameterName];
            } else {
                $pattern = '[^\/]+';
            }

            return '(' . $pattern. ')';
        }, $attribute->path);

        foreach ($attribute->patterns as $parameterName => $pattern) {
            if (! in_array($parameterName, $parameterNames, true)) {
                throw new LogicException(sprintf('Pattern does not match any parameter: "%s".', $parameterName));
            }
        }

        return [$regexp, $parameterNames];
    }
}
