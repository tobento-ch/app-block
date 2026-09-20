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
use Tobento\App\Block\Field\NullField;
use Tobento\App\Block\FieldInterface;

class NullFieldTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $field = new NullField();

        $this->assertInstanceOf(FieldInterface::class, $field);
    }

    public function testRenderMethodReturnsEmptyString(): void
    {
        $field = new NullField();

        $this->assertSame('', $field->render());
    }

    public function testRenderEditableMethodReturnsEmptyString(): void
    {
        $field = new NullField();

        $this->assertSame('', $field->renderEditable());
    }

    public function testValueMethodReturnsEmptyString(): void
    {
        $field = new NullField();

        $this->assertSame('', $field->value());
    }
}