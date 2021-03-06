<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Attribute;

/**
 * Restricts the HTTP methods allowed on a given controller action.
 *
 * Can be used on a controller class (will apply to all controller methods), or on a single method.
 * When used on both, the method attribute will take precedence over the class attribute.
 *
 * This attribute requires the `AllowPlugin`.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
final class Allow
{
    /**
     * The HTTP method(s) the controller action accepts.
     *
     * @var string[]
     */
    public array $methods;

    /**
     * @param string ...$methods
     */
    public function __construct(string ...$methods)
    {
        $this->methods = $methods;
    }
}
