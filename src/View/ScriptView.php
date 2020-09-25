<?php

declare(strict_types=1);

namespace Brick\App\View;

/**
 * Base class for views based on an external view script.
 */
abstract class ScriptView extends AbstractView
{
    public function render() : string
    {
        ob_start();

        try {
            require $this->getScriptPath();
        } finally {
            $content = ob_get_clean();
        }

        return $content;
    }

    /**
     * Returns the absolute path to the view script.
     *
     * This is to be implemented by subclasses.
     */
    abstract protected function getScriptPath() : string;
}
