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

namespace Tobento\App\Block\Test\Field;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Field\HtmlTextEditor;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;

class HtmlTextEditorTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<p>Hello</p>',
            field: 'content',
            translatable: false,
            itemIndex: null,
            toolbar: [],
            view: $view
        );

        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testRenderMethodReturnsSanitizedHtml(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<script>alert("x")</script><div>Hello</div>',
            field: 'content',
            translatable: false,
            itemIndex: null,
            toolbar: [],
            view: $view
        );

        $this->assertSame('<div>Hello</div>', $field->render());
    }

    public function testRenderEditableMethodRendersEditorWrapper(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<div>Hello</div>',
            field: 'answer',
            translatable: true,
            itemIndex: null,
            toolbar: [],
            view: $view
        );

        $output = $field->renderEditable();

        $this->assertStringContainsString('<div', $output);
        $this->assertStringContainsString('data-editor=""', $output);
        $this->assertStringContainsString('data-editor-field="answer"', $output);
        $this->assertStringContainsString('data-editor-translatable="1"', $output);
        $this->assertStringContainsString('<div>Hello</div>', $output);
    }

    public function testRenderEditableMethodIncludesToolbarWhenProvided(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<p>Text</p>',
            field: 'body',
            translatable: false,
            itemIndex: null,
            toolbar: ['bold', 'italic'],
            view: $view
        );

        $output = $field->renderEditable();

        $this->assertStringContainsString(
            "data-editor='{&quot;toolbar&quot;:[&quot;bold&quot;,&quot;italic&quot;]}'",
            $output
        );
        $this->assertStringContainsString('data-editor-field="body"', $output);
        $this->assertStringContainsString('data-editor-translatable="0"', $output);
    }

    public function testRenderEditableMethodIncludesItemIndex(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<p>Item</p>',
            field: 'text',
            translatable: false,
            itemIndex: 3,
            toolbar: [],
            view: $view
        );

        $output = $field->renderEditable();

        $this->assertStringContainsString('data-editor-item="3"', $output);
    }

    public function testWithToolbarOverridesToolbar(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<p>X</p>',
            field: 'field',
            translatable: false,
            itemIndex: null,
            toolbar: [],
            view: $view
        );

        $field->withToolbar(['link', 'underline']);

        $output = $field->renderEditable();

        $this->assertStringContainsString(
            "data-editor='{&quot;toolbar&quot;:[&quot;link&quot;,&quot;underline&quot;]}'",
            $output
        );
    }

    public function testValueMethodReturnsRawHtml(): void
    {
        $view = Factory::createView();

        $field = new HtmlTextEditor(
            html: '<strong>Raw</strong>',
            field: 'field',
            translatable: false,
            itemIndex: null,
            toolbar: [],
            view: $view
        );

        $this->assertSame('<strong>Raw</strong>', $field->value());
    }
}