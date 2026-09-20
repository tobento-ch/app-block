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

namespace Tobento\App\Block\Test\Block;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Block\Repository;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Block\Option\Options;
use Tobento\App\Block\Test\Factory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;

class RepositoryTest extends TestCase
{
    private function createRepo(string $table): RepositoryInterface
    {
        return Factory::createStorageRepository(
            table: $table,
            columns: [
                new Column\Id(),
                new Column\Text('title'),
                new Column\Text('content'),
                new Column\Boolean('active'),
            ],
        );
    }
    
    public function testImplementsBlockInterface(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: null,
            taxonomyRepository: null
        );

        $this->assertInstanceOf(BlockInterface::class, $block);
    }

    public function testRenderReturnsEmptyStringWhenNoItemRepository(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: null,
            taxonomyRepository: null
        );

        $this->assertSame('', $block->render());
    }

    public function testRenderFetchesItemsAndRendersTemplate(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');

        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [],
            taxonomyIds: [],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertStringContainsString('<div class="block block-repository">', $output);
    }

    public function testRenderFiltersByItemIds(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');

        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [2],
            taxonomyIds: [],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertStringContainsString('B', $output);
        $this->assertStringNotContainsString('A', $output);
    }

    public function testRenderResolvesTaxonomyIds(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');
        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $taxonomyRepo = $this->createRepo('taxonomy');

        $resolve = function(RepositoryInterface $taxonomyRepo, array $ids): array {
            return [2];
        };

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: $taxonomyRepo,
            resolveTaxonomyIds: $resolve,
            itemIds: [],
            taxonomyIds: [99],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertStringContainsString('B', $output);
        $this->assertStringNotContainsString('A', $output);
    }

    public function testOptionsMethodReturnsOptions(): void
    {
        $view = Factory::createView();
        $options = new Options(options: ['foo' => 'bar']);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: null,
            taxonomyRepository: null
        );

        $this->assertSame($options, $block->options());
        $this->assertSame(['foo' => 'bar'], $block->options()->all());
    }
    
    public function testRenderMethodOutputsRepositoryHtml(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');

        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [],
            taxonomyIds: [],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        // Assert the real HTML output from tests/views/block/repository.php
        $this->assertStringContainsString('<div class="block block-repository">', $output);
        $this->assertStringContainsString('<div class="item">A</div>', $output);
        $this->assertStringContainsString('<div class="item">B</div>', $output);
    }
    
    public function testRenderMethodAppliesItemBaseWhere(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');

        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => false]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [],
            taxonomyIds: [],
            itemBaseWhere: ['active' => true],   // only active items
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertStringContainsString('<div class="item">B</div>', $output);
        $this->assertStringNotContainsString('<div class="item">A</div>', $output);
    }
    
    public function testRenderMethodAppliesItemsOrderBy(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');

        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [],
            taxonomyIds: [],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: ['title' => 'desc'],   // B then A
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertTrue(
            strpos($output, '<div class="item">B</div>') <
            strpos($output, '<div class="item">A</div>')
        );
    }
    
    public function testRenderMethodAppliesMaxNumberOfItems(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');

        $repo->create(['id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);
        $repo->create(['id' => 3, 'title' => 'C', 'content' => 'Three', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [],
            taxonomyIds: [],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 1,   // limit to 1 item
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertSame(1, substr_count($output, '<div class="item">'));
    }
    
    public function testRenderMethodAppliesTaxonomyBaseWhere(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = $this->createRepo('items');
        $repo->create(['id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $taxonomyRepo = $this->createRepo('taxonomy');
        $taxonomyRepo->create(['id' => 99, 'title' => 'Cat1', 'content' => '', 'active' => false]);
        $taxonomyRepo->create(['id' => 100, 'title' => 'Cat2', 'content' => '', 'active' => true]);

        $resolve = function(RepositoryInterface $taxonomyRepo, array $ids): array {
            $cats = $taxonomyRepo->findAll(where: ['active' => true]);
            return [2]; // always return item 2
        };

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: $taxonomyRepo,
            resolveTaxonomyIds: $resolve,
            itemIds: [],
            taxonomyIds: [99, 100],
            itemBaseWhere: [],
            taxonomyBaseWhere: ['active' => true],   // only active categories
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'id',
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertStringContainsString('<div class="item">B</div>', $output);
    }
    
    public function testRenderMethodUsesCustomIdName(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);

        $repo = Factory::createStorageRepository(
            table: 'items',
            columns: [
                new Column\Id('product_id'),
                new Column\Text('title'),
                new Column\Text('content'),
                new Column\Boolean('active'),
            ],
        );

        $repo->create(['product_id' => 1, 'title' => 'A', 'content' => 'One', 'active' => true]);
        $repo->create(['product_id' => 2, 'title' => 'B', 'content' => 'Two', 'active' => true]);

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $repo,
            taxonomyRepository: null,
            resolveTaxonomyIds: null,
            itemIds: [2],
            taxonomyIds: [],
            itemBaseWhere: [],
            taxonomyBaseWhere: [],
            itemsOrderBy: [],
            maxNumberOfItems: 10,
            idName: 'product_id',   // custom ID name
            generateImagesInBackground: true,
            viewName: null
        );

        $output = $block->render();

        $this->assertStringContainsString('<div class="item">B</div>', $output);
        $this->assertStringNotContainsString('<div class="item">A</div>', $output);
    }
    
    public function testGetterMethods(): void
    {
        $view = Factory::createView();
        $options = new Options(options: []);
        $itemRepo = $this->createRepo('items');
        $taxonomyRepo = $this->createRepo('taxonomy');
        $resolver = fn($repo, $ids) => ['resolved'];

        $block = new Repository(
            view: $view,
            options: $options,
            itemRepository: $itemRepo,
            taxonomyRepository: $taxonomyRepo,
            resolveTaxonomyIds: $resolver,
            itemIds: [1, 2],
            taxonomyIds: [7, 8],
            itemBaseWhere: ['active' => true],
            taxonomyBaseWhere: ['foo' => 'bar'],
            itemsOrderBy: ['title' => 'asc'],
            maxNumberOfItems: 12,
            idName: 'id',
            generateImagesInBackground: false,
            viewName: 'block/custom-view'
        );

        $this->assertSame(['title' => 'asc'], $block->itemsOrderBy());
        $this->assertSame($resolver, $block->resolveTaxonomyIds());
        $this->assertSame('block/custom-view', $block->viewName());
        $this->assertSame(12, $block->maxNumberOfItems());
        $this->assertSame([1, 2], $block->itemIds());
        $this->assertSame([7, 8], $block->taxonomyIds());
        $this->assertFalse($block->generateImagesInBackground());
    }
}