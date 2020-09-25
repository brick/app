<?php

declare(strict_types=1);

namespace Brick\App\Controller;

use Brick\App\View\View;
use Brick\DI\Inject;
use Brick\DI\Injector;
use Brick\Http\Response;

/**
 * Base controller class with helper methods for common cases.
 */
abstract class AbstractController
{
    private Injector|null $injector = null;

    #[Inject]
    public function setInjector(Injector $injector) : void
    {
        $this->injector = $injector;
    }

    protected function renderAsString(View $view) : string
    {
        if ($this->injector) {
            $this->injector->inject($view);
        }

        return $view->render();
    }

    /**
     * Renders a View in a Response object.
     */
    protected function render(View $view) : Response
    {
        return $this->html($this->renderAsString($view));
    }

    /**
     * Returns a plain text response.
     *
     * @param string $text The text content.
     */
    protected function text(string $text) : Response
    {
        return $this->createResponse($text, 'text/plain');
    }

    /**
     * Returns an HTML response.
     *
     * @param string $html The HTML document.
     */
    protected function html(string $html) : Response
    {
        return $this->createResponse($html, 'text/html');
    }

    /**
     * @param string $xml The XML document.
     */
    protected function xml(string $xml) : Response
    {
        return $this->createResponse($xml, 'application/xml');
    }

    /**
     * Returns a JSON response.
     *
     * @param mixed $data   The data to encode, or a valid JSON string if `$encode` == `false`.
     * @param bool  $encode Whether to JSON-encode the data.
     */
    protected function json($data, bool $encode = true) : Response
    {
        if ($encode) {
            $data = json_encode($data);
        }

        return $this->createResponse($data, 'application/json');
    }

    private function createResponse(string $data, string $contentType) : Response
    {
        return (new Response())
            ->setContent($data)
            ->setHeader('Content-Type', $contentType);
    }

    protected function redirect(string $uri, int $statusCode = 302) : Response
    {
        return (new Response())
            ->setStatusCode($statusCode)
            ->setHeader('Location', $uri);
    }

    /**
     * Escapes a string for inclusion in an HTML or XML document.
     */
    protected function escape(string $text) : string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
