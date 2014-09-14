<?php

namespace Brick\App\Plugin;

use Brick\App\Event\RouteMatchedEvent;
use Brick\App\Controller\Annotation\Secure;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpRedirectException;

/**
 * Enforces the protocol allowed on a controller with the Secure annotation.
 *
 * If the Secure annotation is present on a controller class or method, HTTPS is enforced.
 * If the annotation is not present, both protocols are allowed, unless forceUnsecured() has been called,
 * in which case HTTP is enforced instead.
 *
 * The protocol is enforced with a 301 permanent redirect.
 */
class SecurePlugin extends AbstractAnnotationPlugin
{
    /**
     * @var boolean
     */
    private $forceUnsecured = false;

    /**
     * @return SecurePlugin
     */
    public function forceUnsecured()
    {
        $this->forceUnsecured = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->addListener(RouteMatchedEvent::class, function(RouteMatchedEvent $event)
        {
            $controller = $event->getRouteMatch()->getControllerReflection();
            $request    = $event->getRequest();

            $secure = $this->hasControllerAnnotation($controller, Secure::class);

            if ($secure !== $request->isSecure()) {
                if ($secure || $this->forceUnsecured) {
                    $url = preg_replace_callback('/^https?/', function (array $matches) {
                        return $matches[0] == 'http' ? 'https' : 'http';
                    }, $request->getUrl());

                    throw new HttpRedirectException($url, 301);
                }
            }
        });
    }
}
