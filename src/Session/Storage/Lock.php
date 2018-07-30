<?php

declare(strict_types=1);

namespace Brick\App\Session\Storage;

class Lock
{
    /**
     * A storage-specific lock context.
     *
     * @var mixed
     */
    private $context;

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     *
     * @return void
     */
    public function setContext($context) : void
    {
        $this->context = $context;
    }
}
