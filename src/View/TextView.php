<?php

declare(strict_types=1);

namespace Brick\App\View;

/**
 * Simply renders any given text or HTML.
 */
class TextView extends AbstractView
{
    /**
     * @var string
     */
    private string $html;

    /**
     * Private constructor. Use factory methods to obtain an instance.
     *
     * @param string $string
     * @param bool   $escape
     */
    private function __construct(string $string, bool $escape)
    {
        $this->html = $escape ? $this->html($string) : (string) $string;
    }

    /**
     * @param string $text
     *
     * @return TextView
     */
    public static function text(string $text) : self
    {
        return new self($text, true);
    }

    /**
     * {@inheritdoc}
     */
    public function render() : string
    {
        return $this->html;
    }
}
