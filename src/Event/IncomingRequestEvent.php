<?php

declare(strict_types=1);

namespace Brick\App\Event;

use Brick\Http\Request;

/**
 * Event dispatched as soon as the application receives a Request.
 */
final class IncomingRequestEvent
{
    /**
     * The request.
     *
     * @var Request
     */
    private $request;

    /**
     * @param Request $request The request.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
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
}
