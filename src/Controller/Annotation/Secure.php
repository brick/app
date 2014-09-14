<?php

namespace Brick\App\Controller\Annotation;

/**
 * Forces a controller to be accessed on an HTTPS connection.
 *
 * Can be used on a controller class (will apply to all controller methods), or on a single method.
 *
 * This annotation requires the `SecurePlugin`.
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Secure extends AbstractAnnotation
{
}
