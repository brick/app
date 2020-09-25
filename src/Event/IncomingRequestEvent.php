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
     */
    private Request $request;

    /**
     * @param Request $request The request.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the request.
     */
    public function getRequest() : Request
    {
        return $this->request;
    }
}
