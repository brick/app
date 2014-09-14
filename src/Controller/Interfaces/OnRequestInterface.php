<?php

namespace Brick\App\Controller\Interfaces;

use Brick\Http\Request;

/**
 * Allows controller classes to have a method called before any controller method in the class.
 *
 * This interface requires the OnRequestResponsePlugin to be registered with the application.
 */
interface OnRequestInterface
{
    /**
     * This method will be called before any controller method in the class is invocated.
     *
     * It is allowed to throw HTTP exceptions, and therefore can perform checks common to all
     * controller methods in the class, and redirect / return an HTTP error code if necessary.
     *
     * @param \Brick\Http\Request $request
     *
     * @return void
     *
     * @throws \Brick\Http\Exception\HttpException
     */
    public function onRequest(Request $request);
}
