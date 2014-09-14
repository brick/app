<?php

namespace Brick\App\Controller\Annotation;

use Brick\Http\Request;

/**
 * Base class for QueryParam and PostParam.
 */
abstract class RequestParam
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $bindTo;

    /**
     * @var array
     */
    private $options;

    /**
     * Class constructor.
     *
     * @param array $values
     *
     * @throws \RuntimeException
     */
    public function __construct(array $values)
    {
        if (isset($values['name'])) {
            $name = $values['name'];
        } elseif (isset($values['value'])) {
            $name = $values['value'];
        } else {
            throw new \RuntimeException($this->getAnnotationName() . ' requires a parameter name.');
        }

        $this->name = $name;
        $this->bindTo = isset($values['bindTo']) ? $values['bindTo'] : $name;

        unset($values['name'], $values['value'], $values['bindTo']);

        $this->options = $values;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getBindTo()
    {
        return $this->bindTo;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    private function getAnnotationName()
    {
        return '@' . (new \ReflectionObject($this))->getShortName();
    }

    /**
     * Returns the request parameter type: query or post.
     *
     * @return string
     */
    abstract public function getParameterType();

    /**
     * Returns the relevant query/post parameters from the request.
     *
     * @param \Brick\Http\Request $request
     *
     * @return array
     */
    abstract public function getRequestParameters(Request $request);
}
