<?php

declare(strict_types=1);

namespace Brick\App\Controller\Attribute;

use Attribute;
use Brick\Http\Request;

/**
 * This attribute requires the `RequestParamPlugin`.
 */
#[Attribute]
final class PostParam extends RequestParam
{
    public function getParameterType() : string
    {
        return 'post';
    }

    public function getRequestParameters(Request $request) : array
    {
        return $request->getPost();
    }
}