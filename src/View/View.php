<?php

namespace Brick\App\View;

/**
 * Interface that all views must implement.
 */
interface View
{
    /**
     * Renders the view as a string.
     *
     * @return string
     */
    public function render();
}
