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

namespace Tobento\App\Block\Test\Factory;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\BlockEntity;
use Tobento\App\Block\Block\Option\OptionsFactory;
use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Factory\AbstractItems;
use Tobento\App\Block\Field;
use Tobento\App\Block\Test\Factory as TestFactory;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\View\ViewInterface;

class AbstractItemsTest extends TestCase
{
    private function createFactory(array $mapping = [], array $translatable = []): AbstractItems
    {
        $view = TestFactory::createView();
        $pictureGenerator = TestFactory::createPictureGenerator();
        $optionsFactory = new OptionsFactory();

        return new class($view, $optionsFactory, $pictureGenerator, $mapping, $translatable) extends AbstractItems {
            public function __construct(
                ViewInterface $view,
                OptionsFactoryInterface $optionsFactory,
                private PictureGeneratorInterface $pictureGenerator,
                private array $mapping,
                private array $translatable,
            ) {
                parent::__construct($view, $optionsFactory);
            }

            public function type(): string
            {
                return 'test-items';
            }

            protected function configureFieldMapping(): iterable
            {
                return $this->mapping;
            }

            protected function configureTranslatableFields(): array
            {
                return $this->translatable;
            }

            protected function getService(string $name): mixed
            {
                return $this->pictureGenerator;
            }
        };
    }

    public function testTypeMethod(): void
    {
        $factory = $this->createFactory();
        $this->assertSame('test-items', $factory->type());
    }

    public function testViewNameMethodReturnsDefault(): void
    {
        $factory = $this->createFactory();
        $this->assertSame('block/items', $factory->viewName());
    }

    public function testWithViewNamespaceReturnsNewInstance(): void
    {
        $factory = $this->createFactory();
        $new = $factory->withViewNamespace('mail');

        $this->assertNotSame($factory, $new);
        $this->assertNull($factory->viewNamespace());
        $this->assertSame('mail', $new->viewNamespace());
    }

    public function testCreateBlockWithNoItemsRendersEmpty(): void
    {
        $factory = $this->createFactory(['title' => Field\Text::class]);

        $block = $factory->createBlock([]);

        $this->assertIsString($block->render());
    }

    public function testCreateBlockHydratesTextFieldPerItem(): void
    {
        $factory = $this->createFactory(['title' => Field\Text::class]);

        $block = $factory->createBlock([
            'items' => [
                ['title' => 'First'],
                ['title' => 'Second'],
            ],
        ]);

        $this->assertStringContainsString('First', $block->render());
        $this->assertStringContainsString('Second', $block->render());
    }

    public function testCreateBlockSkipsUnmappedFieldName(): void
    {
        $factory = $this->createFactory(['title' => Field\Text::class]);

        // 'subtitle' is not in the mapping and should simply be ignored.
        $block = $factory->createBlock([
            'items' => [
                ['title' => 'Kept', 'subtitle' => 'Dropped'],
            ],
        ]);

        $this->assertStringContainsString('Kept', $block->render());
        $this->assertStringNotContainsString('Dropped', $block->render());
    }

    public function testCreateFieldReturnsNullForUnmappedClass(): void
    {
        $factory = $this->createFactory(['title' => \stdClass::class]);

        $block = $factory->createBlock([
            'items' => [
                ['title' => 'whatever'],
            ],
        ]);

        $this->assertIsString($block->render());
    }

    public function testCreateBlockHydratesDataField(): void
    {
        $factory = $this->createFactory(['meta' => Field\Data::class]);

        $block = $factory->createBlock([
            'items' => [
                ['meta' => ['featured' => true]],
            ],
        ]);

        $this->assertIsString($block->render());
    }

    public function testCreateBlockHydratesFileField(): void
    {
        $factory = $this->createFactory(['attachment' => Field\File::class]);

        $block = $factory->createBlock([
            'items' => [
                ['attachment' => ['src' => 'doc.pdf', 'storage' => 'uploads-private']],
            ],
        ]);

        $this->assertStringContainsString('doc.pdf', $block->render());
    }

    public function testCreateBlockHydratesHtmlFieldAsSanitizedRawHtml(): void
    {
        $factory = $this->createFactory(['body' => Field\Html::class]);

        $block = $factory->createBlock([
            'items' => [
                ['body' => '<p>raw</p><script>alert(1)</script>'],
            ],
        ]);

        $this->assertStringContainsString('<p>raw</p>', $block->render());
        $this->assertStringNotContainsString('<script>', $block->render());
    }

    public function testCreateBlockHydratesImageField(): void
    {
        $factory = $this->createFactory(['image' => Field\Image::class]);

        $block = $factory->createBlock([
            'items' => [
                ['image' => ['src' => 'photo.jpg', 'storage' => 'uploads-private', 'alt' => 'A photo']],
            ],
        ]);

        $this->assertStringContainsString('src="photo.jpg"', $block->render());
    }

    public function testCreateBlockImageFieldDefaultsWhenRawIsNotArray(): void
    {
        $factory = $this->createFactory(['image' => Field\Image::class]);

        $block = $factory->createBlock([
            'items' => [
                ['image' => 'not-an-array'],
            ],
        ]);

        $this->assertIsString($block->render());
    }

    public function testCreateBlockHtmlTextEditorFieldSetsItemIndexAttribute(): void
    {
        $factory = $this->createFactory(
            mapping: ['answer' => Field\HtmlTextEditor::class],
            translatable: ['answer'],
        );

        $block = $factory->createBlock([
            'items' => [
                ['answer' => '<p>First answer</p>'],
                ['answer' => '<p>Second answer</p>'],
            ],
            'editable' => true,
        ]);

        $html = $block->render();

        $this->assertStringContainsString('data-editor-item="0"', $html);
        $this->assertStringContainsString('data-editor-item="1"', $html);
        $this->assertStringContainsString('data-editor-field="answer"', $html);
        $this->assertStringContainsString('data-editor-translatable="1"', $html);
    }

    public function testLocalizeItemsResolvesActiveLocaleFromArray(): void
    {
        $factory = $this->createFactory(
            mapping: ['question' => Field\Text::class],
            translatable: ['question'],
        );

        $entity = new BlockEntity([
            'type' => 'test-items',
            'options' => [],
            'editable' => true,
            'locale' => 'de',
            'data' => [
                'items' => [
                    [
                        'question' => ['en' => 'English Q', 'de' => 'German Q'],
                    ],
                ],
            ],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertStringContainsString('German Q', $block->render());
        $this->assertStringNotContainsString('English Q', $block->render());
    }

    public function testLocalizeItemsFallsBackToFirstLocaleWhenActiveLocaleMissing(): void
    {
        $factory = $this->createFactory(
            mapping: ['question' => Field\Text::class],
            translatable: ['question'],
        );

        $entity = new BlockEntity([
            'type' => 'test-items',
            'options' => [],
            'editable' => true,
            'locale' => 'fr', // not present in the item's translations
            'data' => [
                'items' => [
                    [
                        'question' => ['en' => 'English Q', 'de' => 'German Q'],
                    ],
                ],
            ],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertStringContainsString('English Q', $block->render());
    }

    public function testLocalizeItemsLeavesNonTranslatableFieldUntouched(): void
    {
        $factory = $this->createFactory(
            mapping: ['icon' => Field\Text::class],
            translatable: [], // 'icon' is NOT translatable
        );

        $entity = new BlockEntity([
            'type' => 'test-items',
            'options' => [],
            'editable' => true,
            'locale' => 'en',
            'data' => [
                'items' => [
                    ['icon' => 'star'],
                ],
            ],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertStringContainsString('star', $block->render());
    }

    public function testLocalizeItemsReturnsEmptyStringWhenTranslatableRawIsNotArrayOrString(): void
    {
        $factory = $this->createFactory(
            mapping: ['question' => Field\Text::class],
            translatable: ['question'],
        );

        $entity = new BlockEntity([
            'type' => 'test-items',
            'options' => [],
            'editable' => true,
            'locale' => 'en',
            'data' => [
                'items' => [
                    ['question' => 123], // int, neither string nor array
                ],
            ],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertIsString($block->render());
        $this->assertStringNotContainsString('123', $block->render());
    }

    public function testCreateBlockFromEntityDefaultsToEmptyArrayWhenDataItemsMissing(): void
    {
        $factory = $this->createFactory(['title' => Field\Text::class]);

        $entity = new BlockEntity([
            'type' => 'test-items',
            'options' => [],
            'editable' => true,
            'locale' => 'en',
            'data' => [],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertIsString($block->render());
    }
}