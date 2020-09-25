<?php

declare(strict_types=1);

namespace Brick\App\View;

/**
 * Uses the magic getters and setters to send parameters to the view.
 */
class MagicView extends ScriptView
{
    private string $scriptPath;

    private array $parameters;

    /**
     * @param string $scriptPath The view script path, typically a .phtml file.
     * @param array  $parameters An optional array of parameters to initialize.
     */
    public function __construct(string $scriptPath, array $parameters = [])
    {
        $this->scriptPath = $scriptPath;
        $this->parameters = $parameters;
    }

    public function getScriptPath() : string
    {
        return $this->scriptPath;
    }

    /**
     * @return mixed The value, or null if no parameter by that name.
     */
    public function __get(string $name) : mixed
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @param string $name  The parameter name.
     * @param mixed  $value The parameter value.
     */
    public function __set(string $name, mixed $value) : void
    {
        $this->parameters[$name] = $value;
    }

    public function __isset(string $name) : bool
    {
        return isset($this->parameters[$name]);
    }
}
