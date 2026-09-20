<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Block\Block;

use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Block\Option\OptionsInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Items Block
 *
 * Represents a block composed of multiple item objects, where each
 * item contains one or more FieldInterface instances. Each field is
 * responsible for rendering its own output, while this block provides
 * the shared view context, options, editability state and rendering
 * helpers for the entire item list.
 *
 * Views may call renderField() on each item's fields to automatically
 * select the correct rendering mode (editable or normal) without
 * needing to inspect the block state.
 */
class Items implements BlockInterface
{
    /**
     * @param ViewInterface $view
     * @param OptionsInterface $options
     * @param array<int, array<string, FieldInterface>> $items
     * @param bool $generateImagesInBackground
     * @param bool $editable
     * @param null|string $viewName
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected array $items,
        protected bool $editable,
        protected bool $generateImagesInBackground = true,
        protected null|string $viewName = null,
    ) {}

    /**
     * Render a field according to the block's current mode.
     *
     * If the block is editable, the field's editor-safe HTML is returned
     * via renderEditable(). Otherwise, the sanitized frontend output from
     * render() is used. This method centralizes the decision of which
     * rendering path to use, ensuring that views can always call renderField()
     * without needing to know whether the block is in editable mode.
     *
     * @param FieldInterface $field The field instance to render.
     * @return string The safe HTML output for either frontend or editor mode.
     */
    public function renderField(FieldInterface $field): string
    {
        return $this->editable
            ? $field->renderEditable()
            : $field->render();
    }
    
    /**
     * Returns the rendered block content.
     *
     * @return string
     */
    public function render(): string
    {
        $view = $this->viewName ?: 'block/items';

        return $this->view->render(view: $view, data: [
            'block' => $this,
            'generateImagesInBackground' => $this->generateImagesInBackground,
        ]);
    }    

    /**
     * Returns the items as renderable objects.
     *
     * @return array<int, object>
     */
    public function items(): array
    {
        $items = [];

        foreach ($this->items as $itemFields) {
            $items[] = $this->createItem($itemFields);
        }

        return $items;
    }

    /**
     * Wraps a single item (array of FieldInterface objects)
     * into a renderable item object.
     *
     * @param array<string, FieldInterface> $fields
     * @return object
     */
    protected function createItem(array $fields): object
    {
        return new class($fields)
        {
            /**
             * @param array<string, FieldInterface> $fields
             */
            public function __construct(
                protected array $fields,
            ) {}

            /**
             * Returns true if the field exists.
             *
             * @param string $name
             * @return bool
             */
            public function has(string $name): bool
            {
                return isset($this->fields[$name]);
            }

            /**
             * Returns a renderable field object.
             *
             * @param string $name
             * @return FieldInterface
             */
            public function get(string $name): FieldInterface
            {
                return $this->fields[$name] ?? new Field\NullField();
            }
            
            /**
             * Returns a data field object.
             *
             * @param string $name
             * @return Field\Data
             */
            public function data(string $name): Field\Data
            {
                $field = $this->get($name);
                return $field instanceof Field\Data ? $field : new Field\Data();
            }

            /**
             * Returns all fields.
             *
             * @return array<string, FieldInterface>
             */
            public function all(): array
            {
                return $this->fields;
            }
        };
    }

    /**
     * Returns the options.
     *
     * @return OptionsInterface
     */
    public function options(): OptionsInterface
    {
        return $this->options;
    }
}