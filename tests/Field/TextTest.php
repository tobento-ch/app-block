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
use Tobento\App\Block\Field\Text;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;

class TextTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $view = Factory::createView();

        $field = new Text(
            text: 'Hello',
            view: $view
        );

        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testRenderMethodEscapesText(): void
    {
        $view = Factory::createView();

        $field = new Text(
            text: '<b>bold</b> & "quoted"',
            view: $view
        );

        $output = $field->render();

        $this->assertSame(
            '&lt;b&gt;bold&lt;/b&gt; &amp; &quot;quoted&quot;',
            $output
        );
    }

    public function testRenderMethodReturnsEmptyStringForEmptyText(): void
    {
        $view = Factory::createView();

        $field = new Text(
            text: '',
            view: $view
        );

        $this->assertSame('', $field->render());
    }

    public function testRenderEditableMethodReturnsSameAsRender(): void
    {
        $view = Factory::createView();

        $field = new Text(
            text: 'Editable',
            view: $view
        );

        $this->assertSame($field->render(), $field->renderEditable());
    }

    public function testValueMethodReturnsRawText(): void
    {
        $view = Factory::createView();

        $field = new Text(
            text: 'Raw Value',
            view: $view
        );

        $this->assertSame('Raw Value', $field->value());
    }
}