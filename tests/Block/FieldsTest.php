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

namespace Tobento\App\Block\Test\Block;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Block\Fields;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Block\Option\Options;
use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;
use Tobento\Service\View\ViewInterface;

class FieldsTest extends TestCase
{
    public function testImplementsBlockInterface(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [],
            editable: false
        );

        $this->assertInstanceOf(BlockInterface::class, $block);
    }

    public function testRenderFieldMethodUsesRenderWhenNotEditable(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $field = new Field\Text(text: 'hello', view: $view);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [],
            editable: false
        );

        $this->assertSame($field->render(), $block->renderField($field));
    }

    public function testRenderFieldMethodUsesRenderEditableWhenEditable(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $field = new Field\HtmlTextEditor(
            html: '<b>bold</b>',
            field: 'content',
            translatable: false,
            itemIndex: null,
            toolbar: ['bold', 'italic'],
            view: $view
        );

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [],
            editable: true
        );

        $this->assertSame($field->renderEditable(), $block->renderField($field));
    }

    public function testFieldsMethodReturnsAccessorObject(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [],
            editable: false
        );

        $accessor1 = $block->fields();
        $accessor2 = $block->fields();

        $this->assertSame($accessor1, $accessor2);
        $this->assertIsObject($accessor1);
    }

    public function testFieldsAccessorHasMethod(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $field = new Field\Text(text: 'hello', view: $view);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: ['title' => $field],
            editable: false
        );

        $accessor = $block->fields();

        $this->assertTrue($accessor->has('title'));
        $this->assertFalse($accessor->has('missing'));
    }

    public function testFieldsAccessorGetMethodReturnsFieldOrNullField(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $field = new Field\Html(html: '<p>test</p>', view: $view);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: ['content' => $field],
            editable: false
        );

        $accessor = $block->fields();

        $this->assertSame($field, $accessor->get('content'));
        $this->assertInstanceOf(Field\NullField::class, $accessor->get('missing'));
    }

    public function testFieldsAccessorDataMethodReturnsDataField(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $dataField = new Field\Data(data: ['x' => 1]);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [
                'meta' => $dataField,
                'title' => new Field\Text(text: 'hello', view: $view),
            ],
            editable: false
        );

        $accessor = $block->fields();

        $this->assertSame($dataField, $accessor->data('meta'));
        $this->assertInstanceOf(Field\Data::class, $accessor->data('title'));
        $this->assertInstanceOf(Field\Data::class, $accessor->data('missing'));
    }

    public function testFieldsAccessorAllMethodReturnsAllFields(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $fields = [
            'title' => new Field\Text(text: 'hello', view: $view),
            'list' => new Field\ListField(view: $view, items: ['a']),
        ];

        $block = new Fields(
            view: $view,
            options: $options,
            fields: $fields,
            editable: false
        );

        $accessor = $block->fields();

        $this->assertSame($fields, $accessor->all());
    }

    public function testOptionsMethodReturnsOptions(): void
    {
        $view = Factory::createView();
        $options = new Options(options: ['foo' => 'bar']);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [],
            editable: false
        );

        $this->assertSame($options, $block->options());
        $this->assertSame(['foo' => 'bar'], $block->options()->all());
    }

    public function testRenderMethodOutputsBlockFieldsHtml(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Fields(
            view: $view,
            options: $options,
            fields: [],
            editable: false
        );

        $output = $block->render();

        $this->assertStringContainsString('<div class="block block-fields">', $output);
    }
}