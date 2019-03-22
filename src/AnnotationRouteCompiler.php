<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\App\Controller\Annotation\Route;

use Doctrine\Common\Annotations\Reader;

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
    private $annotationReader;

    /**
     * AnnotationRouteCompiler constructor.
     *
     * @param Reader $annotationReader The annotation reader.
     */
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
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

            $prefixRegexp = '';
            $classParameterNames = [];

            foreach ($this->annotationReader->getClassAnnotations($reflectionClass) as $annotation) {
                if ($annotation instanceof Route) {
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

                        $result[] = [
                            $pathRegexp,
                            $httpMethods,
                            $className,
                            $methodName,
                            $classParameterNames,
                            $methodParameterNames
                        ];
                    }
                }
            }
        }

        return $result;
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
    public function processAnnotation(Route $annotation) : array
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
