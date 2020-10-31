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
     *
     * @var string
     */
    private string $class;

    /**
     * The packed data.
     *
     * Must be an integer, a string, or a non-empty array of integers and strings.
     *
     * @var int|string|array
     */
    private $data;

    /**
     * @param string           $class The canonical class name of the object.
     * @param int|string|array $data  The packed data.
     */
    public function __construct(string $class, $data)
    {
        $this->class = $class;
        $this->data  = $data;
    }

    /**
     * Returns the canonical class name of the object.
     *
     * @return string
     */
    public function getClass() : string
    {
        return $this->class;
    }

    /**
     * Returns the packed data.
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
