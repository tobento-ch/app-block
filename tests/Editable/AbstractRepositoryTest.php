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
use Tobento\App\Block\Editable\AbstractRepository;
use Tobento\App\Block\Editable\Option\Options;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;

require_once __DIR__.'/../trans_function.php';

class AbstractRepositoryTest extends TestCase
{
    private function createBlock(null|string $taxonomyRepository = 'App\CategoryRepository'): AbstractRepository
    {
        return new class(new Options(options: []), $taxonomyRepository) extends AbstractRepository {

            public function __construct(
                Options $options,
                private null|string $taxonomyRepo,
            ) {
                parent::__construct($options);
            }

            public function type(): string { return 'test-repository'; }
            public function title(): string { return 'Test Repository'; }
            public function description(): string { return 'Test block'; }
            public function icon(): string { return '<svg></svg>'; }

            protected function itemRepository(): string
            {
                return 'App\ArticleRepository';
            }

            protected function itemBaseWhere(): array
            {
                return ['status' => 'published'];
            }

            protected function itemToOption(object $item): Field\Option
            {
                return new Field\Option(value: 1, text: 'Item');
            }

            protected function taxonomyRepository(): null|string
            {
                return $this->taxonomyRepo;
            }

            protected function taxonomyBaseWhere(): array
            {
                return [];
            }

            protected function taxonomyItemToOption(object $item): Field\Option
            {
                return new Field\Option(value: 1, text: 'Category');
            }
        };
    }

    public function testTypeMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('test-repository', $block->type());
    }

    public function testTitleMethod(): void
    {
        $block = $this->createBlock();
        $this->assertSame('Test Repository', $block->title());
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
        $this->assertSame(['type' => 'test-repository'], $block->defaultBlock());
    }

    public function testWithDefaultFieldsReturnsNewInstance(): void
    {
        $block = $this->createBlock();
        $new = $block->withDefaultFields('items');

        $this->assertNotSame($block, $new);
    }

    public function testWithDefaultFieldsLimitsConfiguredFields(): void
    {
        $block = $this->createBlock()->withDefaultFields('items');
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $names = array_map(fn(FieldInterface $f) => $f->name(), $fields);

        $this->assertContains('data.itemIds', $names);
        $this->assertNotContains('data.sortBy', $names);
        $this->assertNotContains('data.limit', $names);
        $this->assertNotContains('data.taxonomyIds', $names);
    }

    public function testConfigureFieldsIncludesSortByFieldByDefault(): void
    {
        $block = $this->createBlock();
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $sortBy = null;
        foreach ($fields as $field) {
            if ($field->name() === 'data.sortBy') {
                $sortBy = $field;
            }
        }

        $this->assertInstanceOf(Field\Select::class, $sortBy);
    }

    public function testConfigureFieldsIncludesLimitFieldByDefault(): void
    {
        $block = $this->createBlock();
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $limit = null;
        foreach ($fields as $field) {
            if ($field->name() === 'data.limit') {
                $limit = $field;
            }
        }

        $this->assertInstanceOf(Field\Text::class, $limit);
    }

    public function testConfigureFieldsIncludesItemsFieldByDefault(): void
    {
        $block = $this->createBlock();
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $items = null;
        foreach ($fields as $field) {
            if ($field->name() === 'data.itemIds') {
                $items = $field;
            }
        }

        $this->assertInstanceOf(Field\Options::class, $items);
    }

    public function testConfigureFieldsIncludesTaxonomyFieldByDefault(): void
    {
        $block = $this->createBlock(taxonomyRepository: 'App\CategoryRepository');
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $names = array_map(fn(FieldInterface $f) => $f->name(), $fields);

        $this->assertContains('data.taxonomyIds', $names);
    }
    
    public function testConfigureFieldsIncludesTaxonomyFieldWhenExplicitlyRequested(): void
    {
        $block = $this->createBlock(taxonomyRepository: 'App\CategoryRepository')
            ->withDefaultFields('taxonomy');

        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $names = array_map(fn(FieldInterface $f) => $f->name(), $fields);

        $this->assertContains('data.taxonomyIds', $names);
    }

    public function testConfigureFieldsExcludesTaxonomyFieldWhenNoTaxonomyRepository(): void
    {
        $block = $this->createBlock(taxonomyRepository: null)
            ->withDefaultFields('taxonomy');

        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        $names = array_map(fn(FieldInterface $f) => $f->name(), $fields);

        $this->assertNotContains('data.taxonomyIds', $names);
    }

    public function testConfigureFieldsAppendsOptionFields(): void
    {
        $block = $this->createBlock()->withDefaultFields();
        $fields = iterator_to_array($block->configureFields(new Action\Store()));

        // With an empty Options([]) instance and no default field names,
        // configureFields() should yield nothing at all.
        $this->assertCount(0, $fields);
    }

    public function testToFieldsReturnsBlockUnchanged(): void
    {
        $block = $this->createBlock();

        $result = $block->toFields(
            block: ['data' => ['sortBy' => 'date', 'limit' => '3']],
            action: new Action\Store()
        );

        $this->assertSame(['data' => ['sortBy' => 'date', 'limit' => '3']], $result);
    }
}