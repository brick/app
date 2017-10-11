<?php

declare(strict_types=1);

namespace Brick\App;

use Brick\Event\EventDispatcher;

/**
 * Interface that plugins must implement to add functionality to the application.
 */
interface Plugin
{
    /**
     * Registers the plugin's event listeners on the application's event dispatcher.
     *
     * @param EventDispatcher $dispatcher
     *
     * @return void
     */
    public function register(EventDispatcher $dispatcher) : void;
}
