<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

use Brick\Geo\Geometry;
use Brick\Geo\Exception\GeometryException;

/**
 * Handles conversion between geometry objects and strings.
 */
class GeometryPacker implements ObjectPacker
{
    /**
     * The default SRID to assign to the geometry when reading WKT.
     *
     * @var int
     */
    private $srid;

    /**
     * @param int $srid The default SRID to assign to the geometry when reading WKT.
     */
    public function __construct(int $srid = 0)
    {
        $this->srid = $srid;
    }

    /**
     * {@inheritdoc}
     */
    public function pack(object $object) : ?PackedObject
    {
        if ($object instanceof Geometry) {
            return new PackedObject(Geometry::class, $object->asText());
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function unpack(PackedObject $packedObject) : ?object
    {
        $class = $packedObject->getClass();
        $data  = $packedObject->getData();

        if ($class === Geometry::class || is_subclass_of($class, Geometry::class)) {
            try {
                $geometry = Geometry::fromText($data, $this->srid);

                if (! $geometry instanceof $class) {
                    throw new Exception\ObjectNotConvertibleException(sprintf(
                        'Expected instance of %s, got instance of %s.',
                        $class,
                        get_class($geometry)
                    ));
                }

                return $geometry;
            } catch (GeometryException $e) {
                throw new Exception\ObjectNotConvertibleException($e->getMessage(), 0, $e);
            }
        }

        return null;
    }
}