<?php

declare(strict_types=1);

namespace Brick\App\View;

/**
 * Concatenates several views.
 */
class ConcatView implements View
{
    use Helper\PartialViewHelper;

    /**
     * @var View[]
     */
    private array $views;

    public function __construct(View ...$views)
    {
        $this->views = $views;
    }

    public function render() : string
    {
        $result = '';

        foreach ($this->views as $view) {
            $result .= $this->partial($view);
        }

        return $result;
    }
}
