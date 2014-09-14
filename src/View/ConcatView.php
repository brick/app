<?php

namespace Brick\App\View;

/**
 * Concatenates two views.
 */
class ConcatView implements View
{
    use Helper\PartialViewHelper;

    /**
     * @var \Brick\App\View\View
     */
    private $a;

    /**
     * @var \Brick\App\View\View
     */
    private $b;

    /**
     * @param \Brick\App\View\View $a
     * @param \Brick\App\View\View $b
     */
    public function __construct(View $a, View $b)
    {
        $this->a = $a;
        $this->b = $b;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return $this->partial($this->a) . $this->partial($this->b);
    }
}
