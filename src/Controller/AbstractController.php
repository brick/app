<?php

namespace Brick\App\Controller;

use Brick\App\View\View;
use Brick\Di\Annotation\Inject;
use Brick\Di\Injector;
use Brick\Http\Response;

/**
 * Base controller class with helper methods for common cases.
 */
abstract class AbstractController
{
    /**
     * @var \Brick\Di\Injector|null
     */
    private $injector = null;

    /**
     * @Inject
     *
     * @param Injector $injector
     */
    public function setInjector(Injector $injector)
    {
        $this->injector = $injector;
    }

    /**
     * @param \Brick\App\View\View $view
     *
     * @return string
     */
    protected function renderAsString(View $view)
    {
        if ($this->injector) {
            $this->injector->inject($view);
        }

        return $view->render();
    }

    /**
     * Renders a View in a Response object.
     *
     * @param \Brick\App\View\View $view
     *
     * @return \Brick\Http\Response
     */
    protected function render(View $view)
    {
        return $this->html($this->renderAsString($view));
    }

    /**
     * Returns a plain text response.
     *
     * @param string $text The text content.
     *
     * @return \Brick\Http\Response
     */
    protected function text($text)
    {
        return $this->createResponse($text, 'text/plain');
    }

    /**
     * Returns an HTML response.
     *
     * @param string $html The HTML document.
     *
     * @return \Brick\Http\Response
     */
    protected function html($html)
    {
        return $this->createResponse($html, 'text/html');
    }

    /**
     * @param string $xml The XML document.
     *
     * @return Response
     */
    protected function xml($xml)
    {
        return $this->createResponse($xml, 'application/xml');
    }

    /**
     * Returns a JSON response.
     *
     * @param mixed   $data   The data to encode, or a valid JSON string if `$encode` == `false`.
     * @param boolean $encode Whether to JSON-encode the data.
     *
     * @return \Brick\Http\Response
     */
    protected function json($data, $encode = true)
    {
        if ($encode) {
            $data = json_encode($data);
        }

        return $this->createResponse($data, 'application/json');
    }

    /**
     * @param string $data
     * @param string $contentType
     *
     * @return Response
     */
    private function createResponse($data, $contentType)
    {
        return (new Response())
            ->setContent($data)
            ->setHeader('Content-Type', $contentType);
    }

    /**
     * @param string  $uri
     * @param integer $statusCode
     *
     * @return \Brick\Http\Response
     */
    protected function redirect($uri, $statusCode = 302)
    {
        return (new Response())
            ->setStatusCode($statusCode)
            ->setHeader('Location', $uri);
    }

    /**
     * Escapes a string for inclusion in an HTML or XML document.
     *
     * @param string $text
     *
     * @return string
     */
    protected function escape($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
