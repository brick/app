<?php

namespace Brick\App\View\Helper;

use Brick\Di\Injector;
use Brick\App\View\View;

/**
 * This view helper allows to render a View from within another View (referred to as a partial view).
 */
trait PartialViewHelper
{
    /**
     * @var \Brick\Di\Injector|null
     */
    private $injector;

    /**
     * @Brick\Di\Annotation\Inject
     *
     * @param \Brick\Di\Injector $injector
     *
     * @return void
     */
    final public function setInjector(Injector $injector)
    {
        $this->injector = $injector;
    }

    /**
     * Renders a partial View.
     *
     * @param \Brick\App\View\View $view The View object to render.
     *
     * @return string The rendered View.
     */
    final public function partial(View $view) : string
    {
        if ($this->injector) {
            $this->injector->inject($view);
        }

        return $view->render();
    }
}
