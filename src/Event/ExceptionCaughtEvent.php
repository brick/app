<?php

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
final class ExceptionCaughtEvent
{
    /**
     * The HTTP exception.
     *
     * @var HttpException
     */
    private $exception;

    /**
     * The request.
     *
     * @var Request
     */
    private $request;

    /**
     * The response.
     *
     * @var Response
     */
    private $response;

    /**
     * @param HttpException $exception The HTTP exception.
     * @param Request       $request   The request.
     * @param Response      $response  The response.
     */
    public function __construct(HttpException $exception, Request $request, Response $response)
    {
        $this->exception = $exception;
        $this->request   = $request;
        $this->response  = $response;
    }

    /**
     * Returns the HTTP exception.
     *
     * @return HttpException
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Returns the request.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response.
     *
     * This response can be modified by listeners.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
