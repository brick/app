<?php

declare(strict_types=1);

namespace Brick\App\View;

use ReflectionClass;
use RuntimeException;

/**
 * Allows to use a specific class associated to each view script.
 */
abstract class ClassView extends ScriptView
{
    /**
     * Returns the view script path.
     *
     * Defaults to the class file path, with a .phtml extension, but can be overridden by child classes.
     *
     * @throws RuntimeException
     */
    protected function getScriptPath() : string
    {
        $class = new ReflectionClass($this->getClassName());
        $path = $class->getFileName();
        $path = preg_replace('/\.php$/', '.phtml', $path, -1, $count);

        if ($count !== 1) {
            throw new RuntimeException('The class filename does not end with .php');
        }

        return $path;
    }

    /**
     * Returns the view class to use to detect the script path.
     *
     * Defaults to the called class, but can be overridden by child classes.
     */
    protected function getClassName() : string
    {
        return get_called_class();
    }
}
