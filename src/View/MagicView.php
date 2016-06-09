<?php

namespace Brick\App\View;

/**
 * Uses the magic getters and setters to send parameters to the view.
 */
class MagicView extends ScriptView
{
    /**
     * @var string
     */
    private $scriptPath;

    /**
     * @var array
     */
    private $parameters;

    /**
     * @param string $scriptPath The view script path, typically a .phtml file.
     * @param array  $parameters An optional array of parameters to initialize.
     */
    public function __construct(string $scriptPath, array $parameters = [])
    {
        $this->scriptPath = $scriptPath;
        $this->parameters = $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getScriptPath()
    {
        return $this->scriptPath;
    }

    /**
     * @param string $name
     *
     * @return mixed The value, or null if no parameter by that name.
     */
    public function __get(string $name)
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * @param string $name  The parameter name.
     * @param mixed  $value The parameter value.
     *
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->parameters[$name] = $value;
    }
}
