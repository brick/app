<?php

declare(strict_types=1);

namespace Brick\App\View;

use Brick\Form\Base;
use Brick\Form\Form;
use Brick\Form\Element;
use Brick\Form\Group;

use Brick\Html\Tag;

/**
 * Renders a form with a simplistic markup.
 */
class FormView implements View
{
    private Form $form;

    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    /**
     * Renders the errors of a Form or an Element as an unordered list.
     */
    private function renderErrors(Base $base) : string
    {
        if (! $base->hasErrors()) {
            return '';
        }

        $html = '';

        foreach ($base->getErrors() as $error) {
            $li = new Tag('li');
            $li->setTextContent($error);
            $html .= $li->render();
        }

        $ul = new Tag('ul');
        $ul->setHtmlContent($html);

        return $ul->render();
    }

    /**
     * Renders an element, along with its label.
     */
    private function renderElement(Element $element) : string
    {
        $label = $element->getLabel();

        if ($label->isEmpty()) {
            return $element->render();
        }

        return $label->render() . $element->render();
    }

    private function renderForm(Form $form) : string
    {
        $html = '';

        foreach ($form->getComponents() as $component) {
            $html .= $this->renderErrors($component);

            if ($component instanceof Element) {
                $html .= $this->renderElement($component);
            } elseif ($component instanceof Group) {
                foreach ($component->getElements() as $element) {
                    $html .= $this->renderElement($element);
                }
            }
        }

        return $html;
    }

    public function render() : string
    {
        return
            $this->renderErrors($this->form) .
            $this->form->open() .
            $this->renderForm($this->form) .
            $this->form->close();
    }
}
