<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Exception\HttpException;
use Brick\Http\Request;
use Throwable;

/**
 * Event dispatched as soon as an exception is caught.
 *
 * If the exception is not an HttpException, it is wrapped in an HttpInternalServerErrorException first,
 * so that this event always receives an HttpException.
 *
 * A default response is created to display the details of the exception.
 * This event provides an opportunity to modify the default response
 * to present a customized error message to the client.
 */
final class UncaughtExceptionEvent
{
    /**
     * The HTTP exception.
     *
     * @var Throwable
     */
    private Throwable $exception;

    /**
     * The request.
     *
     * @var Request
     */
    private Request $request;

    /**
     * The converted HTTP exception.
     *
     * @var HttpException|null
     */
    private ?HttpException $httpException = null;

    /**
     * @param Throwable $exception The caught exception.
     * @param Request   $request   The request.
     */
    public function __construct(Throwable $exception, Request $request)
    {
        $this->exception = $exception;
        $this->request   = $request;
    }

    /**
     * Returns the caught exception.
     *
     * @return Throwable
     */
    public function getException() : Throwable
    {
        return $this->exception;
    }

    /**
     * Returns the request.
     *
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * Returns the response.
     *
     * This response can be modified by listeners.
     *
     * @return HttpException|null
     */
    public function getHttpException() : ?HttpException
    {
        return $this->httpException;
    }

    /**
     * @param HttpException $httpException
     *
     * @return void
     */
    public function setHttpException(HttpException $httpException) : void
    {
        $this->httpException = $httpException;
    }
}
