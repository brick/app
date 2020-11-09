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
final class Allow extends AbstractAnnotation
{
    /**
     * The HTTP method(s) the controller action accepts.
     *
     * @var string[]
     */
    private array $methods;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->methods = $this->getRequiredStringArray($values, 'methods', true);
    }

    /**
     * @return array
     */
    public function getMethods() : array
    {
        return $this->methods;
    }
}