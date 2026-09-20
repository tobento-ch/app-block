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
use Tobento\App\Block\Field\Data;
use Tobento\App\Block\FieldInterface;

class DataTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $this->assertInstanceOf(FieldInterface::class, new Data());
    }

    public function testHasMethodReturnsTrueForExistingKey(): void
    {
        $data = new Data([
            'layout' => [
                'image' => 'foo.jpg',
            ],
        ]);

        $this->assertTrue($data->has('layout.image'));
    }

    public function testHasMethodReturnsFalseForMissingKey(): void
    {
        $data = new Data(['foo' => 'bar']);

        $this->assertFalse($data->has('missing.key'));
    }

    public function testContainsMethodReturnsTrueForExistingValue(): void
    {
        $data = new Data(['a', 'b', 'c']);

        $this->assertTrue($data->contains('b'));
    }

    public function testContainsMethodReturnsFalseForNonExistingValue(): void
    {
        $data = new Data(['a', 'b', 'c']);

        $this->assertFalse($data->contains('x'));
    }

    public function testGetMethodReturnsValueForExistingKey(): void
    {
        $data = new Data([
            'layout' => [
                'image' => 'foo.jpg',
            ],
        ]);

        $this->assertSame('foo.jpg', $data->get('layout.image'));
    }

    public function testGetMethodReturnsDefaultForMissingKey(): void
    {
        $data = new Data(['foo' => 'bar']);

        $this->assertSame('default', $data->get('missing.key', 'default'));
    }

    public function testRenderMethodAlwaysReturnsEmptyString(): void
    {
        $data = new Data(['foo' => 'bar']);

        $this->assertSame('', $data->render());
    }

    public function testRenderEditableMethodReturnsSameAsRender(): void
    {
        $data = new Data(['foo' => 'bar']);

        $this->assertSame('', $data->renderEditable());
    }

    public function testValueMethodReturnsUnderlyingArray(): void
    {
        $raw = ['foo' => 'bar', 'nested' => ['x' => 1]];
        $data = new Data($raw);

        $this->assertSame($raw, $data->value());
    }
}