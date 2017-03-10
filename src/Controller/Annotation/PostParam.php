<?php

namespace Brick\App\Controller\Annotation;

use Brick\Http\Request;

/**
 * This annotation requires the `RequestParamPlugin`.
 *
 * @Annotation
 * @Target("METHOD")
 */
class PostParam extends RequestParam
{
    /**
     * {@inheritdoc}
     */
    public function getParameterType() : string
    {
        return 'post';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParameters(Request $request) : array
    {
        return $request->getPost();
    }
}
