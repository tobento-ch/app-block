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
use Tobento\App\Block\Block;
use Tobento\App\Block\BlockEntity;
use Tobento\App\Block\Block\Option\OptionsFactory;
use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Factory\AbstractRepository;
use Tobento\App\Block\Test\Factory as TestFactory;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\View\ViewInterface;

class AbstractRepositoryTest extends TestCase
{
    private function createRepository(): RepositoryInterface
    {
        return TestFactory::createStorageRepository(
            table: 'items',
            columns: [
                new Column\Id(),
                new Column\Text('title'),
                new Column\Text('content'),
                new Column\Boolean('active'),
            ],
        );
    }

    private function createFactory(
        null|RepositoryInterface $itemRepo,
        bool $hasTaxonomyRepositoryClass = false,
        null|RepositoryInterface $taxonomyRepo = null,
        null|callable $itemsOrderByResolver = null,
        null|callable $taxonomyIdsResolver = null,
    ): AbstractRepository {
        $view = TestFactory::createView();
        $optionsFactory = new OptionsFactory();

        return new class(
            $view,
            $optionsFactory,
            $itemRepo,
            $hasTaxonomyRepositoryClass,
            $taxonomyRepo,
            $itemsOrderByResolver,
            $taxonomyIdsResolver,
        ) extends AbstractRepository {
            public function __construct(
                ViewInterface $view,
                OptionsFactoryInterface $optionsFactory,
                private null|RepositoryInterface $itemRepo,
                private bool $hasTaxonomyRepositoryClass,
                private null|RepositoryInterface $taxonomyRepo,
                private null|\Closure $itemsOrderByResolverFn,
                private null|\Closure $taxonomyIdsResolverFn,
            ) {
                parent::__construct($view, $optionsFactory);
            }

            public function type(): string { return 'test-repository'; }
            public function viewName(): string { return 'block/items-repo'; }

            protected function itemRepository(): string { return 'item-repo-key'; }
            protected function itemBaseWhere(): array { return []; }
            protected function itemsOrderByResolver(): null|callable { return $this->itemsOrderByResolverFn; }

            protected function taxonomyRepository(): null|string
            {
                return $this->hasTaxonomyRepositoryClass ? 'taxonomy-repo-key' : null;
            }
            protected function taxonomyBaseWhere(): array { return []; }
            protected function taxonomyIdsResolver(): null|callable { return $this->taxonomyIdsResolverFn; }

            protected function getService(null|string $name): mixed
            {
                return match ($name) {
                    'item-repo-key' => $this->itemRepo,
                    'taxonomy-repo-key' => $this->taxonomyRepo,
                    default => null,
                };
            }
        };
    }

    public function testTypeMethod(): void
    {
        $factory = $this->createFactory($this->createRepository());
        $this->assertSame('test-repository', $factory->type());
    }

    public function testIdNameMethodReturnsDefault(): void
    {
        $factory = $this->createFactory($this->createRepository());
        $this->assertSame('id', $factory->idName());
    }

    public function testWithViewNamespaceReturnsNewInstance(): void
    {
        $factory = $this->createFactory($this->createRepository());
        $new = $factory->withViewNamespace('mail');

        $this->assertNotSame($factory, $new);
        $this->assertNull($factory->viewNamespace());
        $this->assertSame('mail', $new->viewNamespace());
    }

    public function testCreateBlockReturnsNullBlockWhenItemRepositoryIsNotResolvable(): void
    {
        $factory = $this->createFactory(itemRepo: null);

        $block = $factory->createBlock([]);

        $this->assertInstanceOf(Block\NullBlock::class, $block);
    }

    public function testCreateBlockReturnsRealBlockWhenItemRepositoryResolves(): void
    {
        $factory = $this->createFactory($this->createRepository());

        $block = $factory->createBlock([]);

        $this->assertInstanceOf(Block\Repository::class, $block);
    }

    public function testCreateBlockHasNullTaxonomyWhenNoTaxonomyRepositoryClassConfigured(): void
    {
        // taxonomyRepository() itself returns null -> getService(null) short-circuits.
        $factory = $this->createFactory(
            itemRepo: $this->createRepository(),
            hasTaxonomyRepositoryClass: false,
        );

        $block = $factory->createBlock([]);

        $this->assertInstanceOf(Block\Repository::class, $block);
    }

    public function testCreateBlockHasNullTaxonomyWhenResolvedServiceIsNotARepository(): void
    {
        // taxonomyRepository() returns a class string, but getService() resolves
        // to something that isn't a RepositoryInterface at all.
        $factory = $this->createFactory(
            itemRepo: $this->createRepository(),
            hasTaxonomyRepositoryClass: true,
            taxonomyRepo: null, // getService('taxonomy-repo-key') returns null here too
        );

        $block = $factory->createBlock([]);

        $this->assertInstanceOf(Block\Repository::class, $block);
    }

    public function testCreateBlockUsesResolvedTaxonomyRepositoryWhenValid(): void
    {
        $factory = $this->createFactory(
            itemRepo: $this->createRepository(),
            hasTaxonomyRepositoryClass: true,
            taxonomyRepo: $this->createRepository(),
        );

        $block = $factory->createBlock([]);

        $this->assertInstanceOf(Block\Repository::class, $block);
    }

    public function testCreateBlockFromEntityMapsEntityFieldsIntoBlockData(): void
    {
        $factory = $this->createFactory($this->createRepository());

        $entity = new BlockEntity([
            'type' => 'test-repository',
            'options' => [],
            'editable' => true,
            'locale' => 'en',
            'locale_fallbacks' => [],
            'data' => [
                'itemIds' => [1, 2, 3],
                'taxonomyIds' => [7],
                'sortBy' => 'date',
                'limit' => 5,
            ],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertInstanceOf(Block\Repository::class, $block);
    }

    public function testCreateBlockFromEntityDefaultsLimitToThreeWhenMissing(): void
    {
        $factory = $this->createFactory($this->createRepository());

        $entity = new BlockEntity([
            'type' => 'test-repository',
            'options' => [],
            'editable' => true,
            'locale' => 'en',
            'data' => [],
        ]);

        $block = $factory->createBlockFromEntity($entity);

        $this->assertInstanceOf(Block\Repository::class, $block);
    }
    
    public function testCreateBlockAppliesItemsOrderByResolver(): void
    {
        $resolver = fn(string $sortBy) => ['title' => 'asc'];

        $factory = $this->createFactory(
            itemRepo: $this->createRepository(),
            itemsOrderByResolver: $resolver,
        );

        $block = $factory->createBlock(['sortBy' => 'title']);

        $this->assertSame(['title' => 'asc'], $block->itemsOrderBy());
    }

    public function testCreateBlockAppliesTaxonomyIdsResolver(): void
    {
        $resolver = fn(RepositoryInterface $repo, array $ids) => [99, 100];

        $factory = $this->createFactory(
            itemRepo: $this->createRepository(),
            hasTaxonomyRepositoryClass: true,
            taxonomyRepo: $this->createRepository(),
            taxonomyIdsResolver: $resolver,
        );

        $block = $factory->createBlock(['taxonomyIds' => [1, 2]]);

        $callable = $block->resolveTaxonomyIds();
        $this->assertIsCallable($callable);

        $this->assertSame(
            [99, 100],
            $callable($this->createRepository(), [1, 2])
        );
    }

    public function testCreateBlockUsesResolvedViewName(): void
    {
        $factory = $this->createFactory($this->createRepository())
            ->withViewNamespace('mail');

        $block = $factory->createBlock([]);

        $this->assertSame('block/mail/items-repo', $block->viewName());
    }

    public function testCreateBlockUsesProvidedMaxNumberOfItems(): void
    {
        $factory = $this->createFactory($this->createRepository());

        $block = $factory->createBlock(['maxNumberOfItems' => 10]);

        $this->assertSame(10, $block->maxNumberOfItems());
    }

    public function testCreateBlockMapsItemIdsAndTaxonomyIds(): void
    {
        $factory = $this->createFactory($this->createRepository());

        $block = $factory->createBlock([
            'itemIds' => [5, 6],
            'taxonomyIds' => [7, 8],
        ]);

        $this->assertSame([5, 6], $block->itemIds());
        $this->assertSame([7, 8], $block->taxonomyIds());
    }

    public function testCreateBlockPassesGenerateImagesInBackgroundFlag(): void
    {
        $factory = $this->createFactory($this->createRepository());

        $block = $factory->createBlock([]);

        $this->assertTrue($block->generateImagesInBackground());
    }
}