<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

use Brick\Reflection\ReflectionTools;

/**
 * Packs an object or deeply nested objects for serialization.
 */
class Packer
{
    /**
     * @var ObjectPacker
     */
    private $objectPacker;

    /**
     * @var \Brick\Reflection\ReflectionTools
     */
    private $reflectionTools;

    /**
     * @param ObjectPacker $objectPacker
     */
    public function __construct(ObjectPacker $objectPacker)
    {
        $this->objectPacker = $objectPacker;
        $this->reflectionTools = new ReflectionTools();
    }

    /**
     * Returns a copy of the given variable, with all packable objects packed.
     *
     * @param mixed $variable
     *
     * @return mixed
     */
    public function pack($variable)
    {
        return $this->copy($variable, true);
    }

    /**
     * Returns a copy of the given variable, with all packable objects unpacked.
     *
     * @param mixed $variable
     *
     * @return mixed
     */
    public function unpack($variable)
    {
        return $this->copy($variable, false);
    }

    /**
     * @param mixed $variable The variable to copy.
     * @param bool  $pack     True to pack, false to unpack.
     * @param array $visited  The visited objects, for recursive calls.
     * @param int   $level    The nesting level.
     *
     * @return mixed
     */
    private function copy($variable, bool $pack, array & $visited = [], int $level = 0)
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
                    throw new \RuntimeException('No object packer available for ' . $variable->getClass());
                }

                if ($object !== null) {
                    return $visited[$hash] = $object;
                }
            }

            $class = new \ReflectionClass($variable);
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
