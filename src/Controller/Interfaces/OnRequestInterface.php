<?php

declare(strict_types=1);

namespace Brick\App\Controller\Interfaces;

use Brick\Http\Request;

/**
 * Allows controller classes to have a method called before any controller method in the class.
 *
 * This interface requires the OnRequestResponsePlugin to be registered with the application.
 *
 * @deprecated use OnBeforeAfterPlugin
 */
interface OnRequestInterface
{
    /**
     * This method will be called before any controller method in the class is invocated.
     *
     * It is allowed to throw HTTP exceptions, and therefore can perform checks common to all
     * controller methods in the class, and redirect / return an HTTP error code if necessary.
     *
     * It is also allowed to return an HTTP response, in which case the application flow is
     * short-circuited and the response is returned without the controller being invocated.
     *
     * @param \Brick\Http\Request $request
     *
     * @return \Brick\Http\Response|null
     *
     * @throws \Brick\Http\Exception\HttpException
     */
    public function onRequest(Request $request);
}
