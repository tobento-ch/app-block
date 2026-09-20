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

namespace Tobento\App\Block\Test\Editable;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Editable\AbstractFields;
use Tobento\App\Block\Editable\Option\Classes;
use Tobento\App\Block\Editable\Option\Options;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;

class AbstractFieldsTest extends TestCase
{
    private function createBlock(): AbstractFields
    {
        return new class(new Options(options: [])) extends AbstractFields {

            public function type(): string { return 'test-fields'; }
            public function title(): string { return 'Test Fields'; }
            public function description(): string { return 'Test block'; }
            public function icon(): string { return '<svg></svg>'; }

            protected function configureBlockFields(Action\ActionInterface $action): iterable|FieldsInterface
            {
                yield new Field\Text('data.text');
                yield new Field\FileSource('data.filesrc');
                yield new Field\File('data.file');
                yield new Field\Files('data.files');
            }
        };
    }

    public function testTypeMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('test-fields', $block->type());
    }

    public function testTitleMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('Test Fields', $block->title());
    }

    public function testDescriptionMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('Test block', $block->description());
    }

    public function testIconMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('<svg></svg>', $block->icon());
    }

    public function testDefaultBlockMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame(['type' => 'test-fields'], $block->defaultBlock());
    }

    public function testBlockFieldsMethodReturnsConfiguredFields(): void
    {
        $block = $this->createBlock();
        $fields = $block->blockFields(new Action\Store());

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertCount(4, $fields);
        $this->assertInstanceOf(Field\Text::class, $fields->get('data.text'));
        $this->assertInstanceOf(Field\FileSource::class, $fields->get('data.filesrc'));
        $this->assertInstanceOf(Field\File::class, $fields->get('data.file'));
        $this->assertInstanceOf(Field\Files::class, $fields->get('data.files'));
    }
    
    public function testBlockFieldsMethodValidateFieldsThrowsOnInvalidField(): void
    {
        $block = new class(new Options(options: [])) extends AbstractFields {
            public function type(): string { return 'test-fields'; }
            public function title(): string { return 'Test Fields'; }
            public function description(): string { return 'Test block'; }
            public function icon(): string { return '<svg></svg>'; }
            protected function configureBlockFields(Action\ActionInterface $action): iterable|FieldsInterface
            {
                yield new Field\Buttons(name: 'btns');
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $block->blockFields(new Action\Store());
    }

    public function testConfigureFieldsMethodIncludesOptionFields(): void
    {
        $options = new Options(options: [
            'classes' => new Classes(['classname' => 'A title']),
        ]);

        $block = new class($options) extends AbstractFields {
            public function type(): string { return 'test-fields'; }
            public function title(): string { return 'Test Fields'; }
            public function description(): string { return 'Test block'; }
            public function icon(): string { return '<svg></svg>'; }
            protected function configureBlockFields(Action\ActionInterface $action): iterable|FieldsInterface
            {
                yield new Field\Text(name: 'data.text');
            }
        };

        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $this->assertGreaterThan(1, count($fields)); // options add fields
    }
    
    public function testToFieldsMethodNormalizeFileSourceFieldStoreString(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: ['data' => ['filesrc' => 'icon.svg']],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'filesrc' => [
                    'storage' => 'uploads-private',
                    'path' => 'icon.svg',
                ],
            ],
        ], $result);
    }

    public function testToFieldsMethodNormalizeFileSourceFieldUpdateRemovesField(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: ['data' => ['filesrc' => 'icon.svg']],
            action: new Action\Update()
        );

        $this->assertSame(['data' => []], $result);
    }

    public function testToFieldsMethodNormalizeFileSourceFieldStoreArray(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'filesrc' => [
                        'src' => 'icon.svg',
                        'storage' => 'uploads-private',
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'filesrc' => [
                    'src' => [
                        'storage' => 'uploads-private',
                        'path' => 'icon.svg',
                    ],
                    'storage' => 'uploads-private',
                ],
            ],
        ], $result);
    }

    public function testToFieldsMethodDoesNothingIfFileSourceFieldValueIsNull(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: ['data' => []],
            action: new Action\Store()
        );

        $this->assertSame(['data' => []], $result);
    }    

    public function testToFieldsMethodNormalizeFileFieldStoreString(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: ['data' => ['file' => 'path/to/file.jpg']],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'file' => [
                    'storage' => 'uploads-private',
                    'path' => 'path/to/file.jpg',
                ],
            ],
        ], $result);
    }

    public function testToFieldsMethodNormalizeFileFieldUpdateRemovesFieldSrc(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: ['data' => ['file' => ['src' => 'x.jpg', 'foo' => 'Foo']]],
            action: new Action\Update()
        );

        $this->assertSame(['data' => ['file' => ['foo' => 'Foo']]], $result);
    }
    
    public function testToFieldsMethodNormalizeFileFieldStoreLocalized(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'file' => [
                        'src' => [
                            'en' => 'image-en.jpg',
                            'de' => 'image-de.jpg',
                        ],
                        'storage' => 'uploads-private',
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'file' => [
                    'src' => [
                        'en' => ['storage' => 'uploads-private', 'path' => 'image-en.jpg'],
                        'de' => ['storage' => 'uploads-private', 'path' => 'image-de.jpg'],
                    ],
                    'storage' => 'uploads-private',
                ],
            ],
        ], $result);
    }
    
    public function testToFieldsMethodNormalizeFilesFieldStoreNormalizesMainFile(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'files' => [
                        ['src' => 'main.jpg', 'storage' => 'uploads-private'],
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'files' => [
                    [
                        'src' => ['storage' => 'uploads-private', 'path' => 'main.jpg'],
                        'storage' => 'uploads-private',
                    ],
                ],
            ],
        ], $result);
    }
    
    public function testToFieldsMethodNormalizeFilesFieldUpdateRemovesSrc(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'files' => [
                        ['src' => 'main.jpg', 'storage' => 'uploads-private'],
                    ],
                ],
            ],
            action: new Action\Update()
        );

        $this->assertSame([
            'data' => [
                'files' => [
                    ['storage' => 'uploads-private'],
                ],
            ],
        ], $result);
    }
}