<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

/**
 * Null object packer implementation.
 */
class NullPacker implements ObjectPacker
{
    public function pack(object $object) : PackedObject|null
    {
        return null;
    }

    public function unpack(PackedObject $packedObject) : object|null
    {
        return null;
    }
}
