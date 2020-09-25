<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerReadyEvent;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Plugin;
use Brick\App\Controller\Interfaces\OnRequestInterface;
use Brick\App\Controller\Interfaces\OnResponseInterface;
use Brick\Event\EventDispatcher;
use Brick\Http\Response;

/**
 * Calls `onRequest()` and `onResponse()` on controllers implementing OnRequestInterface and OnResponseInterface.
 *
 * @deprecated use OnBeforeAfterPlugin
 */
class OnRequestResponsePlugin implements Plugin
{
    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(ControllerReadyEvent::class, static function(ControllerReadyEvent $event) {
            $controller = $event->getControllerInstance();

            if ($controller instanceof OnRequestInterface) {
                $response = $controller->onRequest($event->getRequest());

                if ($response instanceof Response) {
                    $event->setResponse($response);
                }
            }
        });

        $dispatcher->addListener(ResponseReceivedEvent::class, static function(ResponseReceivedEvent $event) {
            $controller = $event->getControllerInstance();

            if ($controller instanceof OnResponseInterface) {
                $controller->onResponse($event->getRequest(), $event->getResponse());
            }
        });
    }
}
