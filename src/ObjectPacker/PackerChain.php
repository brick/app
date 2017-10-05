<?php

namespace Brick\App\ObjectPacker;

/**
 * An object packer that chains several packers.
 */
class PackerChain implements ObjectPacker
{
    /**
     * @var ObjectPacker[]
     */
    private $objectPackers = [];

    /**
     * @param ObjectPacker $objectPacker
     *
     * @return void
     */
    public function addObjectPacker(ObjectPacker $objectPacker) : void
    {
        $this->objectPackers[] = $objectPacker;
    }

    /**
     * {@inheritdoc}
     */
    public function pack($object) : ?PackedObject
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
    public function unpack(PackedObject $packedObject)
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
