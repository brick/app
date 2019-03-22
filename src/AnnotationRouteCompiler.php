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
     * @throws \LogicException If conflicting routes are found.
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
                    $prefixRegexp = $annotation->getRegexp();
                    $classParameterNames = $annotation->getParameterNames();

                    break;
                }
            }

            foreach ($reflectionClass->getMethods() as $reflectionMethod) {
                $methodName = $reflectionMethod->getName();

                foreach ($this->annotationReader->getMethodAnnotations($reflectionMethod) as $annotation) {
                    if ($annotation instanceof Route) {
                        $regexp = '/^' . $prefixRegexp . $annotation->getRegexp() . '$/';
                        $methodParameterNames = $annotation->getParameterNames();
                        $httpMethods = $annotation->getMethods();

                        $result[] = [$regexp, $httpMethods, $className, $methodName, $classParameterNames, $methodParameterNames];
                    }
                }
            }
        }

        return $result;
    }
}
