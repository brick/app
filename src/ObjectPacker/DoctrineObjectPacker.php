<?php

namespace Brick\App\ObjectPacker;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Proxy;

/**
 * Doctrine implementation of the ObjectPacker.
 */
class DoctrineObjectPacker implements ObjectPacker
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * Class constructor.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function pack($object) : ?PackedObject
    {
        $uow = $this->em->getUnitOfWork();

        if (! $uow->isInIdentityMap($object)) {
            return null;
        }

        $identity = $uow->getEntityIdentifier($object);

        if (count($identity) === 0) {
            return null;
        }

        return new PackedObject($this->getClass($object), $identity);
    }

    /**
     * {@inheritdoc}
     */
    public function unpack(PackedObject $packedObject)
    {
        return $this->em->getReference($packedObject->getClass(), $packedObject->getData());
    }

    /**
     * @param object $entity
     *
     * @return string
     */
    private function getClass($entity) : string
    {
        return ($entity instanceof Proxy) ? get_parent_class($entity) : get_class($entity);
    }
}
