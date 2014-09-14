<?php

namespace Brick\App\View;

/**
 * Base class for views based on an external view script.
 */
abstract class ScriptView extends AbstractView
{
    /**
     * {@inheritdoc}
     */
    public function render()
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
     *
     * @return string
     */
    abstract protected function getScriptPath();
}
