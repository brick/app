<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Exception\HttpException;
use Brick\Http\Request;
use Brick\Http\Response;

/**
 * Event dispatched when an HttpException is caught.
 *
 * At this stage, any uncaught exception will have been converted to an HttpException via the `UncaughtExceptionEvent`.
 * This event will typically be used to generate an error response with a user-friendly error page.
 */
final class HttpExceptionEvent
{
    /**
     * The HTTP exception.
     */
    private HttpException $exception;

    /**
     * The request.
     */
    private Request $request;

    /**
     * The response.
     */
    private Response $response;

    /**
     * @param HttpException $exception The HTTP exception.
     * @param Request       $request   The request.
     * @param Response      $response  A Response pre-populated with status code & headers.
     */
    public function __construct(HttpException $exception, Request $request, Response $response)
    {
        $this->exception = $exception;
        $this->request   = $request;
        $this->response  = $response;
    }

    /**
     * Returns the HTTP exception.
     */
    public function getException() : HttpException
    {
        return $this->exception;
    }

    /**
     * Returns the request.
     */
    public function getRequest() : Request
    {
        return $this->request;
    }

    /**
     * Returns the response.
     */
    public function getResponse() : Response
    {
        return $this->response;
    }

    /**
     * Updates the response.
     */
    public function setResponse(Response $response) : void
    {
        $this->response = $response;
    }
}
