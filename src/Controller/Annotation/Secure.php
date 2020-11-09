<?php

declare(strict_types=1);

namespace Brick\App\Controller\Annotation;

/**
 * Enforces HTTPS on a controller.
 *
 * Can be used on a controller class (to secure all controller methods), or on a single method.
 * Note that this will also secure subclasses of the controller.
 *
 * If an HSTS (HTTP Strict Transport Security) policy is provided in the annotation,
 * the policy is injected in responses to HTTPS requests on the secured controller.
 *
 * Example HSTS policy: 'max-age=3600; includeSubDomains'.
 * This policy would force the browser to transparently rewrite any http URL to https,
 * on the current domain and all of its subdomains, for the next hour.
 *
 * This annotation requires the `SecurePlugin`.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
final class Secure extends AbstractAnnotation
{
    /**
     * @var string|null
     */
    public ?string $hsts;

    /**
     * @param array $values
     */
    public function __construct(array $values)
    {
        $this->hsts = $this->getOptionalString($values, 'hsts', true);
    }
}