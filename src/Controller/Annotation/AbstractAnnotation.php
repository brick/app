<?php

declare(strict_types=1);

namespace Brick\App\Controller\Annotation;

/**
 * Base class for annotation classes.
 *
 * Annotations do not use Doctrine Annotation's built-in property validator, as it cannot currently recognize union
 * types, in particular string|null. We're using annotation constructors and our own validation methods instead.
 */
abstract class AbstractAnnotation
{
    /**
     * @param array  $values
     * @param string $name
     * @param bool   $isFirst
     *
     * @return int
     *
     * @throws \LogicException
     */
    final protected function getOptionalInt(array $values, string $name, bool $isFirst = false) : ?int
    {
        if (isset($values[$name])) {
            $value = $values[$name];
        } elseif ($isFirst && isset($values['value'])) {
            $value = $values['value'];
        } else {
            return null;
        }

        if (! is_int($value)) {
            throw new \LogicException(sprintf(
                'Attribute "%s" of annotation %s expects an int, %s given.',
                $name,
                $this->getAnnotationName(),
                gettype($value)
            ));
        }

        return $value;
    }
    /**
     * @param array  $values
     * @param string $name
     * @param bool   $isFirst
     *
     * @return string
     *
     * @throws \LogicException
     */
    final protected function getRequiredString(array $values, string $name, bool $isFirst = false) : string
    {
        $value = $this->getOptionalString($values, $name, $isFirst);

        if ($value === null) {
            throw new \LogicException(sprintf(
                'Attribute "%s" of annotation %s is required.',
                $name,
                $this->getAnnotationName()
            ));
        }

        return $value;
    }

    /**
     * @param array  $values
     * @param string $name
     * @param bool   $isFirst
     *
     * @return string|null
     *
     * @throws \LogicException
     */
    final protected function getOptionalString(array $values, string $name, bool $isFirst = false) : ?string
    {
        if (isset($values[$name])) {
            $value = $values[$name];
        } elseif ($isFirst && isset($values['value'])) {
            $value = $values['value'];
        } else {
            return null;
        }

        if (! is_string($value)) {
            throw new \LogicException(sprintf(
                'Attribute "%s" of annotation %s expects a string, %s given.',
                $name,
                $this->getAnnotationName(),
                gettype($value)
            ));
        }

        return $value;
    }

    /**
     * @param array  $values
     * @param string $name
     * @param bool   $isFirst
     *
     * @return string[]
     *
     * @throws \LogicException
     */
    final protected function getRequiredStringArray(array $values, string $name, bool $isFirst = false) : array
    {
        $values = $this->getOptionalStringArray($values, $name, $isFirst);

        if (! $values) {
            throw new \LogicException(sprintf(
                'Attribute "%s" of annotation %s must not be empty.',
                $name,
                $this->getAnnotationName()
            ));
        }

        return $values;
    }

    /**
     * @param array  $values
     * @param string $name
     * @param bool   $isFirst
     *
     * @return string[]
     *
     * @throws \LogicException
     */
    final protected function getOptionalStringArray(array $values, string $name, bool $isFirst = false) : array
    {
        if (isset($values[$name])) {
            $value = $values[$name];
        } elseif ($isFirst && isset($values['value'])) {
            $value = $values['value'];
        } else {
            return [];
        }

        if (is_string($value)) {
            return [$value];
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (! is_string($item)) {
                    throw new \LogicException(sprintf(
                        'Attribute "%s" of annotation %s expects an array of strings, %s found in array.',
                        $name,
                        $this->getAnnotationName(),
                        gettype($item)
                    ));
                }
            }

            return $value;
        }

        throw new \LogicException(sprintf(
            'Attribute "%s" of annotation %s expects a string or array of strings, %s given.',
            $name,
            $this->getAnnotationName(),
            gettype($value)
        ));
    }

    /**
     * @return string
     */
    final protected function getAnnotationName() : string
    {
        return '@' . (new \ReflectionObject($this))->getShortName();
    }
}
