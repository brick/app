<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

use Brick\Reflection\ReflectionTools;
use ReflectionClass;
use RuntimeException;

/**
 * Packs an object or deeply nested objects for serialization.
 */
class Packer
{
    private ObjectPacker $objectPacker;

    private ReflectionTools $reflectionTools;

    public function __construct(ObjectPacker $objectPacker)
    {
        $this->objectPacker = $objectPacker;
        $this->reflectionTools = new ReflectionTools();
    }

    /**
     * Returns a copy of the given variable, with all packable objects packed.
     */
    public function pack(mixed $variable) : mixed
    {
        return $this->copy($variable, true);
    }

    /**
     * Returns a copy of the given variable, with all packable objects unpacked.
     */
    public function unpack(mixed $variable) : mixed
    {
        return $this->copy($variable, false);
    }

    /**
     * @param mixed $variable The variable to copy.
     * @param bool  $pack     True to pack, false to unpack.
     * @param array $visited  The visited objects, for recursive calls.
     * @param int   $level    The nesting level.
     */
    private function copy($variable, bool $pack, array & $visited = [], int $level = 0) : mixed
    {
        if (is_object($variable)) {
            $hash = spl_object_hash($variable);

            if (isset($visited[$hash])) {
                return $visited[$hash];
            }

            if ($pack) {
                $packedObject = $this->objectPacker->pack($variable);

                if ($packedObject !== null) {
                    return $visited[$hash] = $packedObject;
                }
            } elseif ($variable instanceof PackedObject) {
                $object = $this->objectPacker->unpack($variable);

                if ($object === null) {
                    throw new RuntimeException('No object packer available for ' . $variable->getClass());
                }

                if ($object !== null) {
                    return $visited[$hash] = $object;
                }
            }

            $class = new ReflectionClass($variable);
            $properties = $this->reflectionTools->getClassProperties($class);

            if (! $class->isUserDefined()) {
                if ($class->isCloneable()) {
                    return $visited[$hash] = clone $variable;
                }

                return $visited[$hash] = $variable;
            }

            $visited[$hash] = $copy = $class->newInstanceWithoutConstructor();

            foreach ($properties as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($variable);
                $processed = $this->copy($value, $pack, $visited, $level + 1);
                $property->setValue($copy, $processed);
            }

            return $copy;
        }

        if (is_array($variable)) {
            foreach ($variable as & $value) {
                $value = $this->copy($value, $pack, $visited, $level + 1);
            }
        }

        return $variable;
    }
}
