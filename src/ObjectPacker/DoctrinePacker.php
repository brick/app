<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Doctrine implementation of the ObjectPacker.
 */
class DoctrinePacker implements ObjectPacker
{
    private EntityManager $em;

    /**
     * Class constructor.
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function pack(object $object) : PackedObject|null
    {
        $uow = $this->em->getUnitOfWork();

        if (! $uow->isInIdentityMap($object)) {
            return null;
        }

        $identity = $uow->getEntityIdentifier($object);

        $count = count($identity);

        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            $identity = reset($identity);
        }

        return new PackedObject($this->getClass($object), $identity);
    }

    public function unpack(PackedObject $packedObject) : object|null
    {
        $class = $packedObject->getClass();

        if ($this->em->getMetadataFactory()->isTransient($class)) {
            return null;
        }

        return $this->em->getReference($class, $packedObject->getData());
    }

    private function getClass(object $entity) : string
    {
        return ($entity instanceof Proxy) ? get_parent_class($entity) : get_class($entity);
    }
}
