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
    public function getParameterType()
    {
        return 'post';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParameters(Request $request)
    {
        return $request->getPost();
    }
}
