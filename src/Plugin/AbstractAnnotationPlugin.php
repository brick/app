<?php

namespace Brick\App\Plugin;

use Brick\App\Plugin;
use Brick\Reflection\ReflectionTools;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;

/**
 * Base class for plugins checking controller annotations.
 */
abstract class AbstractAnnotationPlugin implements Plugin
{
    /**
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $annotationReader;

    /**
     * @var \Brick\Reflection\ReflectionTools
     */
    protected $reflectionTools;

    /**
     * @param Reader $annotationReader
     */
    public function __construct(Reader $annotationReader)
    {
        AnnotationRegistry::registerLoader(function($class) {
            return class_exists($class);
        });

        $this->annotationReader = $annotationReader;
        $this->reflectionTools  = new ReflectionTools();
    }

    /**
     * Finds an annotation on the controller class or method.
     *
     * If the annotation is found on both the controller class and method, the method annotation is returned.
     * If the annotation is found on several classes in the hierarchy of controller classes,
     * the annotation of the child class is returned.
     *
     * This method does not support controller functions outside a class.
     *
     * @param \ReflectionFunctionAbstract $controller
     * @param string                      $annotationClass
     *
     * @return object|null The annotation, or null if not found.
     */
    protected function getControllerAnnotation(\ReflectionFunctionAbstract $controller, string $annotationClass)
    {
        if ($controller instanceof \ReflectionMethod) {
            $annotations = $this->annotationReader->getMethodAnnotations($controller);
            foreach ($annotations as $annotation) {
                if ($annotation instanceof $annotationClass) {
                    return $annotation;
                }
            }

            $class = $controller->getDeclaringClass();
            $classes = $this->reflectionTools->getClassHierarchy($class);

            foreach ($classes as $class) {
                $annotations = $this->annotationReader->getClassAnnotations($class);
                foreach ($annotations as $annotation) {
                    if ($annotation instanceof $annotationClass) {
                        return $annotation;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Checks whether a controller has an annotation on the class or method.
     *
     * @param \ReflectionFunctionAbstract $controller
     * @param string                      $annotationClass
     *
     * @return bool Whether the annotation is present.
     */
    protected function hasControllerAnnotation(\ReflectionFunctionAbstract $controller, string $annotationClass)
    {
        return $this->getControllerAnnotation($controller, $annotationClass) !== null;
    }
}
