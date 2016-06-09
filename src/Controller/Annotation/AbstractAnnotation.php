<?php

namespace Brick\App\Controller\Annotation;

/**
 * Base class for annotation classes.
 */
abstract class AbstractAnnotation
{
    /**
     * @param array $parameters
     *
     * @throws \RuntimeException
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else {
                throw new \RuntimeException(sprintf('Unknown key "%s" for annotation "@%s"', $key, get_class($this)));
            }
        }
    }
}
