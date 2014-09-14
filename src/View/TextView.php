<?php

namespace Brick\App\View;

/**
 * Simply renders any given text or HTML.
 */
class TextView extends AbstractView
{
    /**
     * @var string
     */
    private $html;

    /**
     * Private constructor. Use factory methods to obtain an instance.
     *
     * @param string  $string
     * @param boolean $escape
     */
    private function __construct($string, $escape)
    {
        $this->html = $escape ? $this->html($string) : (string) $string;
    }

    /**
     * @param string $text
     *
     * @return TextView
     */
    public static function text($text)
    {
        return new self($text, true);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this->html;
    }
}
