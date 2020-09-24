<?php

declare(strict_types=1);

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerInvocatedEvent;
use Brick\App\Event\RouteMatchedEvent;
use Brick\App\Controller\Attribute\Transactional;
use Brick\Event\EventDispatcher;
use Brick\App\RouteMatch;

use Doctrine\DBAL\Connection;

/**
 * Configures the start of a database transaction using attributes.
 */
class TransactionalPlugin extends AbstractAttributePlugin
{
    private Connection $connection;

    /**
     * Class constructor.
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher) : void
    {
        $dispatcher->addListener(RouteMatchedEvent::class, function (RouteMatchedEvent $event) {
            $attribute = $this->getTransactionalAttribute($event->getRouteMatch());

            if ($attribute) {
                $this->connection->setTransactionIsolation($attribute->isolationLevel);
                $this->connection->beginTransaction();
            }
        });

        $dispatcher->addListener(ControllerInvocatedEvent::class, function (ControllerInvocatedEvent $event) {
            $attribute = $this->getTransactionalAttribute($event->getRouteMatch());

            if ($attribute) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollBack();
                }
            }
        });
    }

    /**
     * Returns the Transactional attribute for the controller, or null if the controller is not transactional.
     */
    private function getTransactionalAttribute(RouteMatch $routeMatch) : Transactional|null
    {
        $method = $routeMatch->getControllerReflection();

        $attributes = $method->getAttributes(Transactional::class);

        foreach ($attributes as $attribute) {
            return $attribute;
        }

        return null;
    }
}
