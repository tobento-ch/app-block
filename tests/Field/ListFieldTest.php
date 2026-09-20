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
use Tobento\App\Block\Field\ListField;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;

class ListFieldTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: ['a', 'b']
        );

        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testRenderMethodReturnsEmptyStringWhenItemsAreEmpty(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: []
        );

        $this->assertSame('', $field->render());
    }

    public function testRenderMethodEscapesItems(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: ['<b>bold</b>', 'x & y']
        );

        $output = $field->render();

        $this->assertStringContainsString('<ul class="block-field-list">', $output);
        $this->assertStringContainsString('<li>&lt;b&gt;bold&lt;/b&gt;</li>', $output);
        $this->assertStringContainsString('<li>x &amp; y</li>', $output);
    }

    public function testRenderMethodCastsNumericValues(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: [123, 4.56]
        );

        $output = $field->render();

        $this->assertStringContainsString('<li>123</li>', $output);
        $this->assertStringContainsString('<li>4.56</li>', $output);
    }

    public function testRenderMethodCastsBooleanValues(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: [true, false]
        );

        $output = $field->render();
        
        $this->assertStringContainsString('<li>1</li>', $output);
        $this->assertStringContainsString('<li></li>', $output);
    }

    public function testRenderMethodConvertsNullToEmptyString(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: [null]
        );

        $output = $field->render();

        $this->assertStringContainsString('<li></li>', $output);
    }

    public function testRenderMethodJsonEncodesArraysAndObjects(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: [
                ['a' => 1, 'b' => 2],
                (object)['x' => 'y']
            ]
        );

        $output = $field->render();

        $this->assertStringContainsString('<li>{&quot;a&quot;:1,&quot;b&quot;:2}</li>', $output);
        $this->assertStringContainsString('<li>{&quot;x&quot;:&quot;y&quot;}</li>', $output);
    }

    public function testRenderEditableMethodReturnsSameAsRender(): void
    {
        $view = Factory::createView();

        $field = new ListField(
            view: $view,
            items: ['one', 'two']
        );

        $this->assertSame($field->render(), $field->renderEditable());
    }

    public function testValueMethodReturnsRawItems(): void
    {
        $view = Factory::createView();

        $items = ['a', 1, null];

        $field = new ListField(
            view: $view,
            items: $items
        );

        $this->assertSame($items, $field->value());
    }
}