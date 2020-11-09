<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\IncomingRequestEvent;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Plugin;
use Brick\Event\EventDispatcher;
use Brick\App\Session\Session;

/**
 * Integrates session management in the request/response process.
 */
class SessionPlugin implements Plugin
{
    /**
     * @var Session
     */
    private Session $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(IncomingRequestEvent::class, function(IncomingRequestEvent $event) {
            $this->session->readSessionIdFromRequest($event->getRequest());
        });

        $dispatcher->addListener(ResponseReceivedEvent::class, function(ResponseReceivedEvent $event) {
            $updatedResponse = $this->session->writeSessionIdToResponse($event->getResponse());
            $event->setResponse($updatedResponse);
        });
    }
}
