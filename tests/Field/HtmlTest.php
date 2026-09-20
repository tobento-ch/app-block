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
use Tobento\App\Block\Field\Html;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;

class HtmlTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $view = Factory::createView();
        $field = new Html('<p>Test</p>', $view);

        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testRenderMethodReturnsSanitizedHtml(): void
    {
        $view = Factory::createView();

        $html = '<strong>Hello</strong>';
        $field = new Html($html, $view);

        $this->assertSame($html, $field->render());
    }

    public function testRenderMethodReturnsSanitizedHtmlWhenHtmlContainsScript(): void
    {
        $view = Factory::createView();

        $html = '<script>alert("x")</script>foo';
        $field = new Html($html, $view);

        $this->assertSame('foo', $field->render());
    }

    public function testRenderEditableMethodReturnsSameAsRender(): void
    {
        $view = Factory::createView();

        $html = '<div>Editable</div>';
        $field = new Html($html, $view);

        $this->assertSame($field->render(), $field->renderEditable());
    }

    public function testValueMethodReturnsRawHtml(): void
    {
        $view = Factory::createView();

        $html = '<span>Raw</span>';
        $field = new Html($html, $view);

        $this->assertSame($html, $field->value());
    }
}