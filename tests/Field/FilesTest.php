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
use Tobento\App\Block\Field\Files;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Block\Test\Factory;
use Tobento\App\Crud\Collection\Item;
use Tobento\App\Crud\Collection\Items;

class FilesTest extends TestCase
{
    public function testImplementsFieldInterface(): void
    {
        $view = Factory::createView();
        $items = new Items([]);

        $this->assertInstanceOf(FieldInterface::class, new Files($view, $items));
    }

    public function testFilesMethodReturnsItems(): void
    {
        $view = Factory::createView();
        $items = new Items([
            new Item(attributes: ['src' => 'a.pdf']),
            new Item(attributes: ['src' => 'b.pdf']),
        ]);

        $field = new Files($view, $items);

        $this->assertSame($items, $field->files());
    }

    public function testDefinitionMethodReturnsDefaultDefinition(): void
    {
        $view = Factory::createView();
        $items = new Items([]);

        $field = new Files($view, $items);

        $this->assertSame('block-field-files', $field->definition());
    }

    public function testDefinitionMethodReturnsNamedDefinition(): void
    {
        $view = Factory::createView();
        $items = new Items([]);

        $field = new Files($view, $items, [
            'default' => 'block-field-files',
            'thumb' => 'thumb-def',
        ]);

        $this->assertSame('thumb-def', $field->definition('thumb'));
    }

    public function testWithDefinitionMethodOverridesDefinition(): void
    {
        $view = Factory::createView();
        $items = new Items([]);

        $field = new Files($view, $items);
        $field->withDefinition('new-def');

        $this->assertSame('new-def', $field->definition());
    }

    public function testRenderMethodSkipsItemsWithoutSrc(): void
    {
        $view = Factory::createView();

        $items = new Items([
            new Item(attributes: ['src' => '']), // skipped
            new Item(attributes: ['src' => 'file.pdf', 'storage' => 'docs']),
        ]);

        $field = new Files($view, $items);

        $html = $field->render();

        $this->assertStringContainsString('file.pdf', $html);
        $this->assertStringNotContainsString('<a href=""', $html);
    }

    public function testRenderMethodRendersListOfFiles(): void
    {
        $view = Factory::createView();

        $items = new Items([
            new Item(attributes: [
                'src' => 'a.pdf',
                'storage' => 'docs',
                'name' => 'File A',
            ]),
            new Item(attributes: [
                'src' => 'b.pdf',
                'storage' => 'docs',
                'name' => 'File B',
            ]),
        ]);

        $field = new Files($view, $items);

        $html = $field->render();

        $this->assertStringContainsString('<ul class="block-field-files">', $html);
        $this->assertStringContainsString('media.file.download?storage=docs&amp;path=a.pdf', $html);
        $this->assertStringContainsString('media.file.download?storage=docs&amp;path=b.pdf', $html);
        $this->assertStringContainsString('File A', $html);
        $this->assertStringContainsString('File B', $html);
    }

    public function testRenderMethodFallsBackToFilenameWhenNameMissing(): void
    {
        $view = Factory::createView();

        $items = new Items([
            new Item(attributes: [
                'src' => 'foo.pdf',
                'storage' => 'docs',
            ]),
        ]);

        $field = new Files($view, $items);

        $html = $field->render();

        $this->assertStringContainsString('foo.pdf', $html);
    }

    public function testRenderEditableMethodReturnsSameAsRender(): void
    {
        $view = Factory::createView();

        $items = new Items([
            new Item(attributes: [
                'src' => 'foo.pdf',
                'storage' => 'docs',
            ]),
        ]);

        $field = new Files($view, $items);

        $this->assertSame($field->render(), $field->renderEditable());
    }

    public function testValueMethodReturnsItems(): void
    {
        $view = Factory::createView();
        $items = new Items([
            new Item(attributes: ['src' => 'foo.pdf']),
        ]);

        $field = new Files($view, $items);

        $this->assertSame($items, $field->value());
    }
}