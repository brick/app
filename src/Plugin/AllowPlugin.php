<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\RouteMatchedEvent;
use Brick\App\Controller\Attribute\Allow;
use Brick\Event\EventDispatcher;
use Brick\Http\Exception\HttpMethodNotAllowedException;

/**
 * Enforces the methods allowed on a controller using the Allow attribute.
 */
class AllowPlugin extends AbstractAttributePlugin
{
    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(RouteMatchedEvent::class, function(RouteMatchedEvent $event) {
            $controller = $event->getRouteMatch()->getControllerReflection();

            /** @var Allow|null $allow */
            $allow = $this->getControllerAttribute($controller, Allow::class);

            if ($allow !== null) {
                $method = $event->getRequest()->getMethod();
                $allowedMethods = $allow->methods;

                if (! in_array($method, $allowedMethods)) {
                    throw new HttpMethodNotAllowedException($allowedMethods);
                }
            }
        });
    }
}
