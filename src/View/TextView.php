<?php

declare(strict_types=1);

namespace Brick\App\View;

/**
 * Simply renders any given text or HTML.
 */
class TextView extends AbstractView
{
    private string $html;

    /**
     * Private constructor. Use factory methods to obtain an instance.
     */
    private function __construct(string $string, bool $escape)
    {
        $this->html = $escape ? $this->html($string) : (string) $string;
    }

    public static function text(string $text) : self
    {
        return new self($text, true);
    }

    public function render() : string
    {
        return $this->html;
    }
}
