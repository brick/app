<?php

declare(strict_types=1);

namespace Brick\App\Session;

use Brick\Http\Request;
use Brick\Http\Response;

/**
 * A session based on the client IP address.
 */
class IpSession extends Session
{
    /**
     * @inheritdoc
     */
    protected function readSessionId(Request $request) : void
    {
        $this->id = $request->getClientIp();
    }

    /**
     * @inheritdoc
     */
    protected function writeSessionId(Response $response) : void
    {
    }

    /**
     * {@inheritDoc}
     */
    protected function generateSessionId() : string
    {
        throw new \LogicException('IP session id is always set, this method should never be called.');
    }
}
