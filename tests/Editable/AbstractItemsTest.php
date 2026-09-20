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
use Tobento\App\Block\Editable\AbstractItems;
use Tobento\App\Block\Editable\Option\Options;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldsInterface;

require_once __DIR__.'/../trans_function.php';

class AbstractItemsTest extends TestCase
{
    private function createBlock(): AbstractItems
    {
        return new class(new Options(options: [])) extends AbstractItems {

            public function type(): string { return 'test-items'; }
            public function title(): string { return 'Test Items'; }
            public function description(): string { return 'Test block'; }
            public function icon(): string { return '<svg></svg>'; }

            protected function configureItemFields(Action\ActionInterface $action): iterable|FieldsInterface
            {
                yield new Field\Text('title');
                yield new Field\FileSource('image');
            }
        };
    }

    public function testTypeMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('test-items', $block->type());
    }

    public function testTitleMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('Test Items', $block->title());
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
        $this->assertSame(['type' => 'test-items'], $block->defaultBlock());
    }

    public function testMaxItemsMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame(50, $block->maxItems());
    }

    public function testDefaultItemsMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame(1, $block->defaultItems());
    }

    public function testAddNewItemTextMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('Add new item', $block->addNewItemText());
    }

    public function testItemFieldsMethodReturnsConfiguredFields(): void
    {
        $block = $this->createBlock();
        $fields = $block->itemFields(new Action\Store());

        $this->assertInstanceOf(FieldsInterface::class, $fields);
        $this->assertCount(2, $fields);
        $this->assertInstanceOf(Field\Text::class, $fields->get('title'));
        $this->assertInstanceOf(Field\FileSource::class, $fields->get('image'));
    }

    public function testItemFieldsMethodValidateFieldsThrowsOnInvalidField(): void
    {
        $block = new class(new Options(options: [])) extends AbstractItems {
            public function type(): string { return 'test-items'; }
            public function title(): string { return 'Test Items'; }
            public function description(): string { return 'Test block'; }
            public function icon(): string { return '<svg></svg>'; }
            protected function configureItemFields(Action\ActionInterface $action): iterable|FieldsInterface
            {
                yield new Field\Buttons('invalid');
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $block->itemFields(new Action\Store());
    }

    public function testConfigureFieldsMethodBuildsItemsField(): void
    {
        $block = $this->createBlock();
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $this->assertInstanceOf(Field\Items::class, $fields[0]);
        $this->assertSame('data.items', $fields[0]->name());
    }

    public function testToFieldsMethodStoreNormalizesFileSource(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'items' => [
                        [
                            'title' => 'A',
                            'image' => 'x.jpg',
                        ],
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'items' => [
                    [
                        'title' => 'A',
                        'image' => [
                            'storage' => 'uploads-private',
                            'path' => 'x.jpg',
                        ],
                    ],
                ],
            ],
        ], $result);
    }

    public function testToFieldsMethodUpdateRemovesFileSource(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'items' => [
                        [
                            'title' => 'A',
                            'image' => 'x.jpg',
                        ],
                    ],
                ],
            ],
            action: new Action\Update()
        );

        $this->assertSame([
            'data' => [
                'items' => [
                    [
                        'title' => 'A',
                    ],
                ],
            ],
        ], $result);
    }
    
    public function testToFieldsMethodStoreNormalizesFileSourceArray(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'items' => [
                        [
                            'title' => 'A',
                            'image' => ['src' => 'x.jpg', 'storage' => 'uploads-private'],
                        ],
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame([
            'data' => [
                'items' => [
                    [
                        'title' => 'A',
                        'image' => [
                            'src' => ['storage' => 'uploads-private', 'path' => 'x.jpg'],
                            'storage' => 'uploads-private',
                        ],
                    ],
                ],
            ],
        ], $result);
    }
    
    public function testToFieldsMethodStoreNormalizesFileSourceAcrossMultipleItems(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'items' => [
                        ['title' => 'A', 'image' => 'a.jpg'],
                        ['title' => 'B', 'image' => 'b.jpg'],
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame('a.jpg', $result['data']['items'][0]['image']['path']);
        $this->assertSame('b.jpg', $result['data']['items'][1]['image']['path']);
    }
    
    public function testToFieldsMethodStoreSkipsItemWithNoImage(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'items' => [
                        ['title' => 'A'], // no 'image' key at all
                    ],
                ],
            ],
            action: new Action\Store()
        );

        $this->assertSame(['title' => 'A'], $result['data']['items'][0]);
    }
    
    public function testToFieldsMethodReturnsBlockUnchangedWhenNoItemsKey(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(block: ['data' => []], action: new Action\Store());

        $this->assertSame(['data' => []], $result);
    }
    
    public function testToFieldsMethodUpdateRemovesFileSourceArrayShape(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: [
                'data' => [
                    'items' => [
                        ['title' => 'A', 'image' => ['src' => 'x.jpg', 'storage' => 'uploads-private']],
                    ],
                ],
            ],
            action: new Action\Update()
        );

        $this->assertSame(['title' => 'A'], $result['data']['items'][0]);
    }
}