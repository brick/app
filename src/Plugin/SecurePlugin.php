<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Controller\Attribute\Secure;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Event\RouteMatchedEvent;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpRedirectException;

/**
 * Enforces HTTPS on controllers using the Secure attribute.
 *
 * If the Secure attribute is present on a controller class or method,
 * HTTPS is enforced with a 301 permanent redirect.
 *
 * Additionally, if an HSTS (HTTP Strict Transport Security) policy is provided in the attribute,
 * it is injected in responses to HTTPS requests on this controller.
 */
class SecurePlugin extends AbstractAttributePlugin
{
    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(RouteMatchedEvent::class, function(RouteMatchedEvent $event) {
            $controller = $event->getRouteMatch()->getControllerReflection();
            $request    = $event->getRequest();

            $secure = $this->hasControllerAttribute($controller, Secure::class);

            if ($secure && ! $request->isSecure()) {
                $url = preg_replace('/^http/', 'https', $request->getUrl());

                throw new HttpRedirectException($url, 301);
            }
        });

        $dispatcher->addListener(ResponseReceivedEvent::class, function (ResponseReceivedEvent $event) {
            $controller = $event->getRouteMatch()->getControllerReflection();

            /** @var Secure|null $secure */
            $secure = $this->getControllerAttribute($controller, Secure::class);

            if ($secure && $secure->hsts !== null && $event->getRequest()->isSecure()) {
                $event->getResponse()->setHeader('Strict-Transport-Security', $secure->hsts);
            }
        });
    }
}
