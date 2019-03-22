<?php

declare(strict_types=1);

namespace Brick\App\Controller\Annotation;

/**
 * Restricts the HTTP methods allowed on a given controller action.
 *
 * Can be used on a controller class (will apply to all controller methods), or on a single method.
 * When used on both, the method annotation will take precedence over the class annotation.
 *
 * This annotation requires the `AllowPlugin`.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Allow extends AbstractAnnotation
{
    /**
     * The HTTP method(s) the controller action accepts.
     *
     * @var array
     */
    private $methods = [];

    /**
     * @param string|array $methods
     */
    public function setValue($methods)
    {
        $this->methods = is_array($methods) ? $methods : [$methods];
    }

    /**
     * @return array
     */
    public function getMethods() : array
    {
        return $this->methods;
    }
}
