<?php

declare(strict_types=1);

namespace Brick\App\View\Helper;

use Brick\DI\Inject;
use Brick\DI\Injector;
use Brick\App\View\View;

/**
 * This view helper allows to render a View from within another View (referred to as a partial view).
 */
trait PartialViewHelper
{
    private Injector|null $injector;

    #[Inject]
    final public function setInjector(Injector $injector) : void
    {
        $this->injector = $injector;
    }

    /**
     * Renders a partial View.
     *
     * @param View $view The View object to render.
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
