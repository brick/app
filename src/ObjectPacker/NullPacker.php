<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

/**
 * Null object packer implementation.
 */
class NullPacker implements ObjectPacker
{
    /**
     * {@inheritdoc}
     */
    public function pack($object) : ?PackedObject
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unpack(PackedObject $packedObject)
    {
        return null;
    }
}