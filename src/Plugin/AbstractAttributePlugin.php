<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Plugin;
use Brick\Reflection\ReflectionTools;
use ReflectionAttribute;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Base class for plugins checking controller attributes.
 */
abstract class AbstractAttributePlugin implements Plugin
{
    protected ReflectionTools $reflectionTools;

    public function __construct()
    {
        $this->reflectionTools  = new ReflectionTools();
    }

    /**
     * Finds an attribute on the controller class or method.
     *
     * If the attribute is found on both the controller class and method, the method attribute is returned.
     * If the attribute is found on several classes in the hierarchy of controller classes,
     * the annotaattributetion of the child class is returned.
     *
     * This method does not support controller functions outside a class.
     *
     * @return object|null The attribute, or null if not found.
     */
    protected function getControllerAttribute(ReflectionFunctionAbstract $controller, string $attributeClass) : object|null
    {
        $attributes = $controller->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            return $attribute;
        }

        if ($controller instanceof ReflectionMethod) {
            $class = $controller->getDeclaringClass();
            $classes = $this->reflectionTools->getClassHierarchy($class);

            foreach ($classes as $class) {
                $attributes = $class->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF);

                foreach ($attributes as $attribute) {
                    return $attribute;
                }
            }
        }

        return null;
    }

    /**
     * Checks whether a controller has a given attribute on the class or method.
     *
     * @return bool Whether the attribute is present.
     */
    protected function hasControllerAttribute(ReflectionFunctionAbstract $controller, string $attributeClass) : bool
    {
        return $this->getControllerAttribute($controller, $attributeClass) !== null;
    }
}
