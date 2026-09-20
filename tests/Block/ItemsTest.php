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
use Tobento\App\Block\Block\Items;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Block\Option\Options;
use Tobento\App\Block\Field;
use Tobento\App\Block\Test\Factory;

class ItemsTest extends TestCase
{
    public function testImplementsBlockInterface(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Items(
            view: $view,
            options: $options,
            items: [],
            editable: false
        );

        $this->assertInstanceOf(BlockInterface::class, $block);
    }

    public function testRenderFieldMethodUsesRenderWhenNotEditable(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $field = new Field\Text(text: 'hello', view: $view);

        $block = new Items(
            view: $view,
            options: $options,
            items: [],
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

        $block = new Items(
            view: $view,
            options: $options,
            items: [],
            editable: true
        );

        $this->assertSame($field->renderEditable(), $block->renderField($field));
    }

    public function testItemsMethodReturnsArrayOfAccessorObjects(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $items = [
            [
                'title' => new Field\Text(text: 'Item 1', view: $view),
                'body'  => new Field\Html(html: '<p>Body</p>', view: $view),
            ],
            [
                'title' => new Field\Text(text: 'Item 2', view: $view),
            ],
        ];

        $block = new Items(
            view: $view,
            options: $options,
            items: $items,
            editable: false
        );

        $accessors = $block->items();

        $this->assertCount(2, $accessors);
        $this->assertIsObject($accessors[0]);
        $this->assertIsObject($accessors[1]);
    }

    public function testItemAccessorHasMethod(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $items = [
            [
                'title' => new Field\Text(text: 'Hello', view: $view),
            ],
        ];

        $block = new Items(
            view: $view,
            options: $options,
            items: $items,
            editable: false
        );

        $item = $block->items()[0];

        $this->assertTrue($item->has('title'));
        $this->assertFalse($item->has('missing'));
    }

    public function testItemAccessorGetMethodReturnsFieldOrNullField(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $items = [
            [
                'content' => new Field\Html(html: '<p>Test</p>', view: $view),
            ],
        ];

        $block = new Items(
            view: $view,
            options: $options,
            items: $items,
            editable: false
        );

        $item = $block->items()[0];

        $this->assertInstanceOf(Field\Html::class, $item->get('content'));
        $this->assertInstanceOf(Field\NullField::class, $item->get('missing'));
    }

    public function testItemAccessorDataMethodReturnsDataField(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $dataField = new Field\Data(data: ['x' => 1]);

        $items = [
            [
                'meta' => $dataField,
                'title' => new Field\Text(text: 'Hello', view: $view),
            ],
        ];

        $block = new Items(
            view: $view,
            options: $options,
            items: $items,
            editable: false
        );

        $item = $block->items()[0];

        $this->assertSame($dataField, $item->data('meta'));
        $this->assertInstanceOf(Field\Data::class, $item->data('title'));
        $this->assertInstanceOf(Field\Data::class, $item->data('missing'));
    }

    public function testItemAccessorAllMethodReturnsAllFields(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $fields = [
            'title' => new Field\Text(text: 'Hello', view: $view),
            'body'  => new Field\Html(html: '<p>Body</p>', view: $view),
        ];

        $items = [$fields];

        $block = new Items(
            view: $view,
            options: $options,
            items: $items,
            editable: false
        );

        $item = $block->items()[0];

        $this->assertSame($fields, $item->all());
    }

    public function testOptionsMethodReturnsOptions(): void
    {
        $view = Factory::createView();
        $options = new Options(options: ['foo' => 'bar']);

        $block = new Items(
            view: $view,
            options: $options,
            items: [],
            editable: false
        );

        $this->assertSame($options, $block->options());
        $this->assertSame(['foo' => 'bar'], $block->options()->all());
    }

    public function testRenderMethodOutputsBlockItemsHtml(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Items(
            view: $view,
            options: $options,
            items: [],
            editable: false
        );

        $output = $block->render();

        $this->assertStringContainsString('<div class="block block-items', $output);
    }
}