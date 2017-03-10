<?php

namespace Brick\App\View;

use Brick\Form\Base;
use Brick\Form\Form;
use Brick\Form\Element;
use Brick\Form\Group;

use Brick\Html\ContainerTag as Tag;

/**
 * Renders a form in a table.
 */
class FormTableView implements View
{
    /**
     * @var \Brick\Form\Form
     */
    private $form;

    /**
     * @var string
     */
    private $class;

    /**
     * @param \Brick\Form\Form $form
     * @param string $class
     */
    public function __construct(Form $form, string $class = '')
    {
        $this->form  = $form;
        $this->class = $class;
    }

    /**
     * Renders the errors of a Form or an Element as an unordered list.
     *
     * @param \Brick\Form\Base $base
     *
     * @return string
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
     * @param string $tagName
     * @param string $html
     *
     * @return string
     */
    private function renderCell(string $tagName, string $html) : string
    {
        $td = new Tag($tagName);
        $td->setHtmlContent($html);

        return $td->render();
    }

    /**
     * Renders an element, along with its label.
     *
     * @param \Brick\Form\Element $element
     *
     * @return string
     */
    private function renderElementAsRow(Element $element) : string
    {
        $tr = new Tag('tr');

        $html = $this->renderCell('th', $element->getLabel()->render() . $this->renderErrors($element));
        $html .= $this->renderCell('td', $element->render());

        $tr->setHtmlContent($html);

        return $tr->render();
    }

    /**
     * @param \Brick\Form\Form $form
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    private function renderForm(Form $form) : string
    {
        $html = '';

        foreach ($form->getComponents() as $component) {
            if ($component instanceof Element) {
                $html .= $this->renderElementAsRow($component);
            } elseif ($component instanceof Group) {
                foreach ($component->getElements() as $element) {
                    $html .= $this->renderElementAsRow($element);
                }
            }
        }

        $table = new Tag('table');
        $table->setAttribute('class', $this->class);
        $table->setHtmlContent($html);

        return $table->render();
    }

    /**
     * {@inheritdoc}
     */
    public function render() : string
    {
        return
            $this->renderErrors($this->form) .
            $this->form->open() .
            $this->renderForm($this->form) .
            $this->form->close();
    }
}
