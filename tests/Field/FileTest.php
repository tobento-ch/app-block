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
use Tobento\App\Block\Field\File;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;
use Tobento\App\Crud\Collection\Item;

class FileTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: []);

        $this->assertInstanceOf(FieldInterface::class, new File($view, $item));
    }

    public function testFileMethodReturnsItem(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: ['src' => 'foo.pdf']);

        $field = new File($view, $item);

        $this->assertSame($item, $field->file());
    }

    public function testDefinitionMethodReturnsDefaultDefinition(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: []);

        $field = new File($view, $item);

        $this->assertSame('block-field-file', $field->definition());
    }

    public function testDefinitionMethodReturnsNamedDefinition(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: []);

        $field = new File($view, $item, [
            'default' => 'block-field-file',
            'thumb' => 'thumb-def',
        ]);

        $this->assertSame('thumb-def', $field->definition('thumb'));
    }

    public function testWithDefinitionMethodOverridesDefinition(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: []);

        $field = new File($view, $item);
        $field->withDefinition('new-def');

        $this->assertSame('new-def', $field->definition());
    }

    public function testRenderMethodReturnsEmptyStringWhenSrcMissing(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: []); // no src

        $field = new File($view, $item);

        $this->assertSame('', $field->render());
    }

    public function testRenderMethodReturnsAnchorTag(): void
    {
        $view = Factory::createView();

        $item = new Item(attributes: [
            'src' => 'foo.pdf',
            'storage' => 'docs',
            'title' => 'My File',
        ]);

        $field = new File($view, $item);

        $html = $field->render();

        $this->assertStringContainsString('<a class="block-field-file"', $html);
        $this->assertStringContainsString('media.file.download?storage=docs&amp;path=foo.pdf', $html);
        $this->assertStringContainsString('My File', $html);
    }

    public function testRenderMethodFallsBackToFilenameWhenTitleMissing(): void
    {
        $view = Factory::createView();

        $item = new Item(attributes: [
            'src' => 'foo.pdf',
            'storage' => 'docs',
        ]);

        $field = new File($view, $item);

        $html = $field->render();

        $this->assertStringContainsString('foo.pdf', $html);
    }

    public function testRenderEditableMethodReturnsSameAsRender(): void
    {
        $view = Factory::createView();

        $item = new Item(attributes: [
            'src' => 'foo.pdf',
            'storage' => 'docs',
        ]);

        $field = new File($view, $item);

        $this->assertSame($field->render(), $field->renderEditable());
    }

    public function testValueMethodReturnsItem(): void
    {
        $view = Factory::createView();
        $item = new Item(attributes: ['src' => 'foo.pdf']);

        $field = new File($view, $item);

        $this->assertSame($item, $field->value());
    }
}