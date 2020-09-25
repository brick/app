<?php

declare(strict_types=1);

namespace Brick\App\View;

use Brick\Form\Base;
use Brick\Form\Form;
use Brick\Form\Element;
use Brick\Form\Group;
use Brick\Html\Tag;
use RuntimeException;

/**
 * Renders a form in a table.
 */
class FormTableView implements View
{
    private Form $form;

    private string $class;

    public function __construct(Form $form, string $class = '')
    {
        $this->form  = $form;
        $this->class = $class;
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

    private function renderCell(string $tagName, string $html) : string
    {
        $td = new Tag($tagName);
        $td->setHtmlContent($html);

        return $td->render();
    }

    /**
     * Renders an element, along with its label.
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
     * @throws RuntimeException
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
