<?php

namespace Brick\App\Plugin;

use Brick\App\Controller\Annotation\Secure;
use Brick\App\Event\ResponseReceivedEvent;
use Brick\App\Event\RouteMatchedEvent;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpRedirectException;

/**
 * Enforces HTTPS on controllers with an annotation.
 *
 * If the Secure annotation is present on a controller class or method,
 * HTTPS is enforced with a 301 permanent redirect.
 *
 * Additionally, if an HSTS (HTTP Strict Transport Security) policy is provided in the annotation,
 * it is injected in responses to HTTPS requests on this controller.
 */
class SecurePlugin extends AbstractAnnotationPlugin
{
    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(RouteMatchedEvent::class, function(RouteMatchedEvent $event) {
            $controller = $event->getRouteMatch()->getControllerReflection();
            $request    = $event->getRequest();

            $secure = $this->hasControllerAnnotation($controller, Secure::class);

            if ($secure && ! $request->isSecure()) {
                $url = preg_replace('/^http/', 'https', $request->getUrl());

                throw new HttpRedirectException($url, 301);
            }
        });

        $dispatcher->addListener(ResponseReceivedEvent::class, function (ResponseReceivedEvent $event) {
            $controller = $event->getRouteMatch()->getControllerReflection();

            /** @var Secure|null $secure */
            $secure = $this->getControllerAnnotation($controller, Secure::class);

            if ($secure && $secure->hsts !== null && $event->getRequest()->isSecure()) {
                $event->getResponse()->setHeader('Strict-Transport-Security', $secure->hsts);
            }
        });
    }
}
