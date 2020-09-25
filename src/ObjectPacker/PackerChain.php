<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

/**
 * An object packer that chains several packers.
 */
class PackerChain implements ObjectPacker
{
    /**
     * @var ObjectPacker[]
     */
    private array $objectPackers = [];

    public function addObjectPacker(ObjectPacker $objectPacker) : void
    {
        $this->objectPackers[] = $objectPacker;
    }

    /**
     * {@inheritdoc}
     */
    public function pack(object $object) : PackedObject|null
    {
        foreach ($this->objectPackers as $objectPacker) {
            $packedObject = $objectPacker->pack($object);

            if ($packedObject !== null) {
                return $packedObject;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unpack(PackedObject $packedObject) : object|null
    {
        foreach ($this->objectPackers as $objectPacker) {
            $object = $objectPacker->unpack($packedObject);

            if ($object !== null) {
                return $object;
            }
        }

        return null;
    }
}
