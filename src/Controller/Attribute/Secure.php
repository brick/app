<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Attribute;

/**
 * Enforces HTTPS on a controller.
 *
 * Can be used on a controller class (to secure all controller methods), or on a single method.
 * Note that this will also secure subclasses of the controller.
 *
 * If an HSTS (HTTP Strict Transport Security) policy is provided in the attribute,
 * the policy is injected in responses to HTTPS requests on the secured controller.
 *
 * Example HSTS policy: 'max-age=3600; includeSubDomains'.
 * This policy would force the browser to transparently rewrite any http URL to https,
 * on the current domain and all of its subdomains, for the next hour.
 *
 * This attribute requires the `SecurePlugin`.
 */
#[Attribute]
final class Secure
{
    public string|null $hsts;

    public function __construct(string|null $hsts = null)
    {
        $this->hsts = $hsts;
    }
}
