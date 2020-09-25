<?php

declare(strict_types=1);

namespace Brick\App\View;

/**
 * Interface that all views must implement.
 */
interface View
{
    /**
     * Renders the view as a string.
     */
    public function render() : string;
}
