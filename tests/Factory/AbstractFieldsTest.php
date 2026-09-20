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
use Tobento\App\Block\Factory\AbstractFields;
use Tobento\App\Block\Field;
use Tobento\App\Block\Test\Factory as TestFactory;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\View\ViewInterface;

class AbstractFieldsTest extends TestCase
{
    private function createFactory(): AbstractFields
    {
        $view = TestFactory::createView();
        $pictureGenerator = TestFactory::createPictureGenerator();
        $optionsFactory = new OptionsFactory();

        return new class($view, $optionsFactory, $pictureGenerator) extends AbstractFields {

            public function __construct(
                ViewInterface $view,
                OptionsFactoryInterface $optionsFactory,
                private PictureGeneratorInterface $pictureGenerator,
            ) {
                parent::__construct($view, $optionsFactory);
            }

            public function type(): string
            {
                return 'test-fields';
            }

            protected function configureFieldMapping(): iterable
            {
                return [
                    'translation' => Field\HtmlTextEditor::class,
                    'data.image' => Field\Image::class,
                    'data.text' => Field\Text::class,
                ];
            }

            protected function configureEntityFieldMapping(): array
            {
                return [
                    'translation' => 'translation',
                    'data.image' => 'data.image',
                    'data.text' => 'data.text',
                ];
            }

            protected function configureTranslatableFields(): array
            {
                return ['translation'];
            }

            protected function getService(string $name): mixed
            {
                return $this->pictureGenerator;
            }
        };
    }
    
    private function createFactoryWithMapping(array $mapping): AbstractFields
    {
        $view = TestFactory::createView();
        $pictureGenerator = TestFactory::createPictureGenerator();
        $optionsFactory = new OptionsFactory();

        return new class($view, $optionsFactory, $pictureGenerator, $mapping) extends AbstractFields {
            public function __construct(
                ViewInterface $view,
                OptionsFactoryInterface $optionsFactory,
                private PictureGeneratorInterface $pictureGenerator,
                private array $mapping,
            ) {
                parent::__construct($view, $optionsFactory);
            }

            public function type(): string { return 'test-fields'; }
            protected function configureFieldMapping(): iterable { return $this->mapping; }
            protected function configureEntityFieldMapping(): array { return []; }
            protected function configureTranslatableFields(): array { return []; }
            protected function getService(string $name): mixed { return $this->pictureGenerator; }
        };
    }

    public function testTypeMethod(): void
    {
        $factory = $this->createFactory();
        $this->assertSame('test-fields', $factory->type());
    }

    public function testViewNameMethodReturnsDefault(): void
    {
        $factory = $this->createFactory();
        $this->assertSame('block/fields', $factory->viewName());
    }

    public function testWithViewNamespaceReturnsNewInstance(): void
    {
        $factory = $this->createFactory();
        $new = $factory->withViewNamespace('mail');

        $this->assertNotSame($factory, $new);
        $this->assertNull($factory->viewNamespace());
        $this->assertSame('mail', $new->viewNamespace());
    }

    public function testCreateBlockHydratesTextField(): void
    {
        $factory = $this->createFactory();

        $block = $factory->createBlock([
            'data' => ['text' => 'hello world'],
        ]);

        $this->assertStringContainsString('hello world', $block->render());
    }

    public function testCreateBlockSkipsFieldWhenRawValueIsNull(): void
    {
        $factory = $this->createFactory();

        // 'data.text' is mapped but absent from block data entirely.
        $block = $factory->createBlock([
            'translation' => '<p>lorem</p>',
        ]);

        // Should not throw, and should render without the missing field's content.
        $this->assertStringNotContainsString('data.text', $block->render());
    }

    public function testCreateBlockHydratesHtmlTextEditorFieldAsEditable(): void
    {
        $factory = $this->createFactory();

        $block = $factory->createBlock([
            'translation' => '<p>lorem</p>',
            'editable' => true,
        ]);

        $this->assertStringContainsString(
            'data-editor-field="translation"',
            $block->render()
        );
        $this->assertStringContainsString(
            'data-editor-translatable="1"',
            $block->render()
        );
    }

    public function testCreateBlockHydratesImageField(): void
    {
        $factory = $this->createFactory();

        $block = $factory->createBlock([
            'data' => [
                'image' => [
                    'src' => 'image.jpg',
                    'storage' => 'uploads-private',
                    'alt' => 'An image',
                ],
            ],
        ]);

        $this->assertStringContainsString('src="image.jpg"', $block->render());
    }

    public function testCreateBlockFromEntityAppliesLocalizedTranslatableFields(): void
    {
        $factory = $this->createFactory();

        $entity = new BlockEntity([
            'type' => 'test-fields',
            'options' => [],
            'editable' => true,
            'data' => [],
            'content' => '',
            'translation' => [
                'en' => '<p>localized text</p>',
                'de' => '<p>lokalisierter Text</p>',
            ],
            'translations' => [],
            'locale' => 'en',
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertStringContainsString('localized text', $block->render());
    }
    
    public function testCreateBlockHydratesDataField(): void
    {
        $factory = $this->createFactoryWithMapping(['data.settings' => Field\Data::class]);

        $block = $factory->createBlock([
            'data' => ['settings' => ['display' => ['image', 'name']]],
        ]);

        // Field\Data renders nothing itself — verify via the block's fields accessor.
        $this->assertSame(
            ['display' => ['image', 'name']],
            $block->fields()->data('data.settings')->value()
        );
    }

    public function testCreateBlockDataFieldDefaultsToEmptyArrayWhenRawIsNotArray(): void
    {
        $factory = $this->createFactoryWithMapping(['data.settings' => Field\Data::class]);

        $block = $factory->createBlock([
            'data' => ['settings' => 'not-an-array'],
        ]);

        $this->assertSame([], $block->fields()->data('data.settings')->value());
    }

    public function testCreateBlockHydratesFileField(): void
    {
        $factory = $this->createFactoryWithMapping(['data.attachment' => Field\File::class]);

        $block = $factory->createBlock([
            'data' => [
                'attachment' => ['src' => 'doc.pdf', 'storage' => 'uploads-private', 'title' => 'My Doc'],
            ],
        ]);

        $this->assertStringContainsString('doc.pdf', $block->render());
    }

    public function testCreateBlockHydratesFilesField(): void
    {
        $factory = $this->createFactoryWithMapping(['data.files' => Field\Files::class]);

        $block = $factory->createBlock([
            'data' => [
                'files' => [
                    ['src' => 'a.pdf', 'storage' => 'uploads-private', 'name' => 'A'],
                    ['src' => 'b.pdf', 'storage' => 'uploads-private', 'name' => 'B'],
                ],
            ],
        ]);

        $this->assertStringContainsString('a.pdf', $block->render());
        $this->assertStringContainsString('b.pdf', $block->render());
    }

    public function testCreateBlockHydratesHtmlFieldAsSanitizedRawHtml(): void
    {
        $factory = $this->createFactoryWithMapping(['content' => Field\Html::class]);

        $block = $factory->createBlock([
            'content' => '<p>raw</p><script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('<p>raw</p>', $block->render());
        $this->assertStringNotContainsString('<script>', $block->render());
    }

    public function testCreateBlockHydratesListField(): void
    {
        $factory = $this->createFactoryWithMapping(['data.tags' => Field\ListField::class]);

        $block = $factory->createBlock([
            'data' => ['tags' => ['red', 'blue']],
        ]);

        $this->assertStringContainsString('red', $block->render());
        $this->assertStringContainsString('blue', $block->render());
    }

    public function testCreateBlockListFieldRendersEmptyStringWhenListIsEmpty(): void
    {
        $factory = $this->createFactoryWithMapping(['data.tags' => Field\ListField::class]);

        $block = $factory->createBlock([
            'data' => ['tags' => []],
        ]);

        $this->assertSame('', trim(strip_tags($block->render())));
    }

    public function testCreateFieldReturnsNullForUnmappedClass(): void
    {
        $factory = $this->createFactoryWithMapping(['data.unknown' => \stdClass::class]);

        // Should not throw; the field is simply skipped during hydration.
        $block = $factory->createBlock([
            'data' => ['unknown' => 'whatever'],
        ]);

        $this->assertIsString($block->render());
    }
}