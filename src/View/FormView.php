<?php

namespace Brick\App\View;

use Brick\Form\Base;
use Brick\Form\Form;
use Brick\Form\Element;
use Brick\Form\Group;

use Brick\Html\ContainerTag as Tag;

/**
 * Renders a form with a simplistic markup.
 */
class FormView implements View
{
    /**
     * @var \Brick\Form\Form
     */
    private $form;

    /**
     * @param \Brick\Form\Form $form
     */
    public function __construct(Form $form)
    {
        $this->form = $form;
    }

    /**
     * Renders the errors of a Form or an Element as an unordered list.
     *
     * @param \Brick\Form\Base $base
     * @return string
     */
    private function renderErrors(Base $base)
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
     *
     * @param \Brick\Form\Element $element
     * @return string
     */
    private function renderElement(Element $element)
    {
        return $element->getLabel()->render() . $element->render();
    }

    /**
     * @param \Brick\Form\Form $form
     * @return string
     */
    private function renderForm(Form $form)
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

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        return
            $this->renderErrors($this->form) .
            $this->form->openTag() .
            $this->renderForm($this->form) .
            $this->form->closeTag();
    }
}
