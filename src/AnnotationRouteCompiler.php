<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\App\Controller\Annotation\Route;
use Doctrine\Common\Annotations\Reader;
use TypeError;

/**
 * Compiles Route annotations into an array to be provided to the AnnotationRoute.
 *
 * The result is a list of [regexp, httpMethods, className, methodName, classParameterNames, methodParameterNames].
 */
class AnnotationRouteCompiler
{
    /**
     * The annotation reader.
     *
     * @var Reader
     */
    private Reader $annotationReader;

    /**
     * @var string[]
     */
    private array $defaultMethods;

    /**
     * An array of [path, HTTP methods, controller class, controller method] arrays.
     *
     * @var array
     */
    private array $routes = [];

    /**
     * AnnotationRouteCompiler constructor.
     *
     * @param Reader   $annotationReader The annotation reader.
     * @param string[] $defaultMethods   The HTTP methods allowed by default, if the annotation does not provide them.
     *                                   Defaults to ANY.
     */
    public function __construct(Reader $annotationReader, array $defaultMethods = [])
    {
        foreach ($defaultMethods as $method) {
            if (! is_string($method)) {
                throw new TypeError(sprintf('Default HTTP methods must be an array of string, %s found in array.', gettype($method)));
            }
        }

        $this->annotationReader = $annotationReader;
        $this->defaultMethods   = $defaultMethods;
    }

    /**
     * @param string[] $classNames An array of controller class names to get routes from.
     *
     * @return array
     *
     * @throws \LogicException
     */
    public function compile(array $classNames) : array
    {
        $result = [];

        foreach ($classNames as $className) {
            $reflectionClass = new \ReflectionClass($className);

            $prefixPath = '';
            $prefixRegexp = '';
            $classParameterNames = [];
            $classPriority = null;

            foreach ($this->annotationReader->getClassAnnotations($reflectionClass) as $annotation) {
                if ($annotation instanceof Route) {
                    $prefixPath = $annotation->path;
                    $classPriority = $annotation->priority;
                    [$prefixRegexp, $classParameterNames] = $this->processAnnotation($annotation);

                    break;
                }
            }

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $methodName = $reflectionMethod->getName();

                foreach ($this->annotationReader->getMethodAnnotations($reflectionMethod) as $annotation) {
                    if ($annotation instanceof Route) {
                        [$regexp, $methodParameterNames] = $this->processAnnotation($annotation);

                        $pathRegexp = '/^' . $prefixRegexp . $regexp . '$/';
                        $httpMethods = $annotation->methods;

                        if (! $httpMethods) {
                            $httpMethods = $this->defaultMethods;
                        }

                        $priority = $annotation->priority ?? $classPriority ?? 0;

                        $this->routes[] = [
                            $prefixPath . $annotation->path,
                            $annotation->methods,
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
        }

        // Sort routes by path & methods
        sort($this->routes);

        return $result;
    }

    /**
     * Returns an array of [path, HTTP methods, controller class, controller method] arrays.
     *
     * This array is only available after compile() has been executed.
     *
     * @return array
     */
    public function getRoutes() : array
    {
        return $this->routes;
    }

    /**
     * Creates a path regular expression and infer the parameter names from a Route annotation.
     *
     * @param Route $annotation The annotation to process.
     *
     * @return array The path regexp, and the parameter names.
     *
     * @throws \LogicException
     */
    private function processAnnotation(Route $annotation) : array
    {
        $parameterNames = [];

        $regexp = preg_replace_callback('/\{([^\}]+)\}|(.+?)/', function(array $matches) use ($annotation, & $parameterNames) : string {
            if (isset($matches[2])) {
                return preg_quote($matches[2], '/');
            }

            $parameterName = $matches[1];
            $parameterNames[] = $parameterName;

            if (isset($annotation->patterns[$parameterName])) {
                $pattern = $annotation->patterns[$parameterName];
            } else {
                $pattern = '[^\/]+';
            }

            return '(' . $pattern. ')';
        }, $annotation->path);

        foreach ($annotation->patterns as $parameterName => $pattern) {
            if (! in_array($parameterName, $parameterNames, true)) {
                throw new \LogicException(sprintf('Pattern does not match any parameter: "%s".', $parameterName));
            }
        }

        return [$regexp, $parameterNames];
    }
}
