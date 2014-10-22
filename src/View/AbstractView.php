<?php

namespace Brick\App\View;

/**
 * Provides base functionality common to all views.
 */
abstract class AbstractView implements View
{
    /**
     * HTML-escapes a text string.
     *
     * This is the single most important protection against XSS attacks:
     * any user-originated data, or more generally any data that is not known to be valid and trusted HTML,
     * must be escaped before being displayed in a web page.
     *
     * @param string  $text       The text to escape.
     * @param boolean $lineBreaks Whether to escape line breaks. Defaults to `false`.
     *
     * @return string
     */
    public function html($text, $lineBreaks = false)
    {
        $html = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        return $lineBreaks ? nl2br($html) : $html;
    }
}
