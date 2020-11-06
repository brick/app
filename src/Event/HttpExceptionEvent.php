<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Exception\HttpException;
use Brick\Http\Request;
use Brick\Http\Response;

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
    private ?Response $response = null;

    /**
     * @param HttpException $exception The HTTP exception.
     * @param Request       $request   The request.
     */
    public function __construct(HttpException $exception, Request $request)
    {
        $this->exception = $exception;
        $this->request   = $request;
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
    public function getResponse() : ?Response
    {
        return $this->response;
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function setResponse(Response $response) : void
    {
        $this->response = $response;
    }
}
