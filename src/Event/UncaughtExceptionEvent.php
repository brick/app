<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Exception\HttpException;
use Brick\Http\Request;
use Throwable;

/**
 * Event dispatched when an uncaught exception occurs during request handling.
 *
 * This event gives an opportunity to plugins to control how exceptions are converted to `HttpException`s.
 * If no plugin catches this event and sets an HttpException, the exception will be wrapped in an
 * HttpInternalServerErrorException by the Application.
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
     * Returns the converted HTTP exception, if any.
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
