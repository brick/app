<?php

namespace Brick\App\Plugin;

use Brick\App\Event\ControllerInvocatedEvent;
use Brick\App\Event\RouteMatchedEvent;
use Brick\App\Controller\Annotation\Transactional;
use Brick\Event\EventDispatcher;
use Brick\App\RouteMatch;

use Doctrine\DBAL\Connection;
use Doctrine\Common\Annotations\Reader;

/**
 * Configures the start of a database transaction with annotations.
 */
class TransactionalPlugin extends AbstractAnnotationPlugin
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * Class constructor.
     *
     * @param Connection $connection
     * @param Reader     $annotationReader
     */
    public function __construct(Connection $connection, Reader $annotationReader)
    {
        parent::__construct($annotationReader);

        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function register(EventDispatcher $dispatcher)
    {
        $dispatcher->addListener(RouteMatchedEvent::class, function (RouteMatchedEvent $event) {
            $annotation = $this->getTransactionalAnnotation($event->getRouteMatch());

            if ($annotation) {
                $this->connection->setTransactionIsolation($annotation->getIsolationLevel());
                $this->connection->beginTransaction();
            }
        });

        $dispatcher->addListener(ControllerInvocatedEvent::class, function (ControllerInvocatedEvent $event) {
            $annotation = $this->getTransactionalAnnotation($event->getRouteMatch());

            if ($annotation) {
                if ($this->connection->isTransactionActive()) {
                    $this->connection->rollback();
                }
            }
        });
    }

    /**
     * Returns the Transactional annotation for the controller.
     *
     * @param \Brick\App\RouteMatch $routeMatch
     *
     * @return Transactional|null The annotation, or NULL if the controller is not transactional.
     *
     * @todo add support for annotations on functions when Doctrine will handle them
     */
    private function getTransactionalAnnotation(RouteMatch $routeMatch)
    {
        $method = $routeMatch->getControllerReflection();

        if ($method instanceof \ReflectionMethod) {
            $annotations = $this->annotationReader->getMethodAnnotations($method);

            foreach ($annotations as $annotation) {
                if ($annotation instanceof Transactional) {
                    return $annotation;
                }
            }
        }

        return null;
    }
}
