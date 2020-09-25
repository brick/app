<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

use Brick\App\ObjectPacker\Exception\ObjectNotConvertibleException;
use Brick\App\ObjectPacker\Exception\ObjectNotFoundException;

/**
 * Packs and unpacks objects.
 *
 * The object packers are used for two purposes:
 *
 * - Passing objects as URL parameters, and retrieving them automatically in controllers
 * - Serializing compact representations of objects in sessions, such as ids of entities
 *
 * Object packer examples:
 *
 * - ORM entities can be packed into their int|string|array identity.
 * - GIS geometry objects can be packed into their WKT string representation.
 * - Date/time objects can be packed into their ISO 8601 string representation.
 *
 * Many other usages are possible, as long as objects have a simple packed representation,
 * and can be recovered after packing.
 *
 * Note that the unpack() function must be able to recover an object whose packed representation had integers converted
 * to strings, as this will be the case when transmitted as URL parameters.
 */
interface ObjectPacker
{
    /**
     * Packs a given object into its flattened representation.
     *
     * An equivalent object must be recoverable by using unpack() on the result of this method.
     *
     * @param object $object The object to pack.
     *
     * @return PackedObject|null The packed object, or null if the object is not targeted by this packer.
     *
     * @throws ObjectNotConvertibleException If the object is supported, but is not convertible for some reason.
     */
    public function pack(object $object) : PackedObject|null;

    /**
     * Unpacks an object from its flattened representation.
     *
     * @param PackedObject $packedObject The packed object to unpack.
     *
     * @return object|null The object, or null if the class name is not targeted by this packer.
     *
     * @throws ObjectNotConvertibleException If the class name is supported, but the representation is not valid.
     * @throws ObjectNotFoundException       If the class name is supported, but the object cannot be found.
     */
    public function unpack(PackedObject $packedObject) : object|null;
}
