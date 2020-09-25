<?php

declare(strict_types=1);

namespace Brick\App\ObjectPacker;

/**
 * A packed object, containing its class name and packed data.
 */
class PackedObject
{
    /**
     * The canonical class name of the object.
     */
    private string $class;

    /**
     * The packed data.
     *
     * Must be an integer, a string, or a non-empty array of integers and strings.
     */
    private int|string|array $data;

    /**
     * @param string           $class The canonical class name of the object.
     * @param int|string|array $data  The packed data.
     */
    public function __construct(string $class, int|string|array $data)
    {
        $this->class = $class;
        $this->data  = $data;
    }

    /**
     * Returns the canonical class name of the object.
     */
    public function getClass() : string
    {
        return $this->class;
    }

    /**
     * Returns the packed data.
     */
    public function getData() : mixed
    {
        return $this->data;
    }
}
