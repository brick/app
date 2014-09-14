<?php

namespace Brick\App\Controller\Annotation;

use Brick\Http\Request;

/**
 * This annotation requires the `RequestParamPlugin`.
 *
 * @Annotation
 * @Target("METHOD")
 */
class QueryParam extends RequestParam
{
    /**
     * {@inheritdoc}
     */
    public function getParameterType()
    {
        return 'query';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestParameters(Request $request)
    {
        return $request->getQuery();
    }
}
