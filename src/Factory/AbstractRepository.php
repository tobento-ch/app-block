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

namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Block;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Exception\BlockCreateException;
use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;
use Tobento\Service\Repository\LocalesAware;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\app;

/**
 * Base factory for repository‑driven blocks.
 *
 * Provides default logic for view resolution, locale switching,
 * option creation, and mapping entity data. Concrete factories only
 * need to define the block type and repository classes.
 */
abstract class AbstractRepository implements BlockFactoryInterface
{
    /**
     * Create a new Items factory instance.
     *
     * @param ViewInterface $view
     * @param OptionsFactoryInterface $optionsFactory
     * @param null|string $viewNamespace
     * @param bool $generateImagesInBackground
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsFactoryInterface $optionsFactory,
        protected null|string $viewNamespace = null,
        protected bool $generateImagesInBackground = true,
    ) {}
    
    /**
     * Returns the block type handled by this factory.
     *
     * This value must match the type returned by the corresponding
     * editable block (Editable\AbstractItems::type()) so the block
     * manager can correctly pair editable configuration, hydration,
     * and rendering.
     *
     * @return string
     */
    abstract public function type(): string;
    
    /**
     * Returns the base view name used to render items for this repository block.
     *
     * Concrete factories must return a theme view such as:
     *   block/articles
     *   block/products
     *   block/events
     *
     * This ensures that each repository block has a dedicated, fully
     * customizable theme template instead of relying on a generic fallback.
     *
     * @return string
     */
    abstract public function viewName(): string;

    /**
     * Returns the repository class used to fetch items.
     *
     * @return class-string Class string implementing RepositoryInterface
     */
    abstract protected function itemRepository(): string;
    
    /**
     * Returns base where conditions for item repository queries.
     *
     * @return array<string, mixed>
     */
    abstract protected function itemBaseWhere(): array;
    
    /**
     * Returns the resolver used to convert a sort key into a repository
     * orderBy array. The resolver receives the raw sort key (e.g. "date",
     * "title", "price_low") and must return an associative array suitable
     * for RepositoryInterface::findAll(orderBy: ...).
     *
     * Example return values:
     *   ['date_created' => 'desc']
     *   ['title' => 'asc']
     *   ['price' => 'asc']
     *
     * If null is returned, no ordering will be applied.
     *
     * @return null|callable
     *     fn (string $sortBy): array<string, string>
     */
    abstract protected function itemsOrderByResolver(): null|callable;

    /**
     * Returns the taxonomy repository class or null if not used.
     *
     * @return null|class-string Class string implementing RepositoryInterface
     */
    abstract protected function taxonomyRepository(): null|string;
    
    /**
     * Returns base where conditions for taxonomy repository queries.
     *
     * @return array<string, mixed>
     */
    abstract protected function taxonomyBaseWhere(): array;
    
    /**
     * Returns the resolver used to convert selected taxonomy IDs into
     * item IDs. The resolver receives the taxonomy repository instance
     * and the raw taxonomy IDs selected in the block configuration.
     *
     * It must return an array of item IDs that should be merged into
     * the final item filter. This allows each concrete factory to define
     * its own taxonomy → item resolution logic (e.g. categories, tags,
     * hierarchical taxonomies, or custom relations).
     *
     * Example return values:
     *   [12, 44, 91]
     *   ['a1', 'b7', 'c3']
     *
     * If null is returned, no taxonomy filtering will be applied.
     *
     * @return null|callable
     *     fn (RepositoryInterface $taxonomyRepository, array $taxonomyIds): array<array-key, int|string>
     */
    abstract protected function taxonomyIdsResolver(): null|callable;
    
    /**
     * Returns id name.
     *
     * @return string
     */
    public function idName(): string
    {
        return 'id';
    }
    
    /**
     * Returns a new instance with the specified view namespace.
     *
     * @param null|string $namespace
     * @return static
     */
    public function withViewNamespace(null|string $namespace): static
    {
        $new = clone $this;
        $new->viewNamespace = $namespace;
        return $new;
    }

    /**
     * Returns the view namespace.
     *
     * @return null|string
     */
    public function viewNamespace(): null|string
    {
        return $this->viewNamespace;
    }

    /**
     * Create block.
     *
     * @param array<string, mixed> $block
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlock(array $block): BlockInterface
    {
        $options = $this->optionsFactory->createOptions($block['options'] ?? []);

        $viewName = Helper::resolveViewName(
            view: $this->view,
            name: $this->viewName(),
            namespace: $this->viewNamespace(),
            options: $options,
        );
        
        $itempRepo = $this->getService($this->itemRepository());
        
        if (! $itempRepo instanceof RepositoryInterface) {
            return new Block\NullBlock();
        }
        
        $taxonomyRepo = $this->getService($this->taxonomyRepository());
        
        if (! $taxonomyRepo instanceof RepositoryInterface) {
            $taxonomyRepo = null;
        }
        
        $locale = $block['locale'] ?? null;
        $itemRepo = $this->withLocaleIfSupported($itempRepo, $locale);
        $taxonomyRepo = $this->withLocaleIfSupported($taxonomyRepo, $locale);

        $itemsOrderByResolver = $this->itemsOrderByResolver();
        $itemsOrderBy = $itemsOrderByResolver
            ? $itemsOrderByResolver($block['sortBy'] ?? '')
            : [];

        return new Block\Repository(
            view: $this->view,
            options: $this->optionsFactory->createOptions($block['options'] ?? []),
            itemRepository: $itemRepo,
            taxonomyRepository: $taxonomyRepo,
            resolveTaxonomyIds: $this->taxonomyIdsResolver(),
            itemIds: $block['itemIds'] ?? [],
            taxonomyIds: $block['taxonomyIds'] ?? [],
            itemBaseWhere: $this->itemBaseWhere(),
            taxonomyBaseWhere: $this->taxonomyBaseWhere(),
            itemsOrderBy: $itemsOrderBy,
            maxNumberOfItems: (int)($block['maxNumberOfItems'] ?? 3),
            idName: $this->idName(),
            generateImagesInBackground: $this->generateImagesInBackground,
            viewName: $viewName,
        );
    }

    /**
     * Create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlockFromEntity(BlockEntityInterface $entity): BlockInterface
    {
        return $this->createBlock([
            'type' => $entity->type(),
            'options' => $entity->options(),
            'editable' => $entity->editable(),
            'itemIds' => $entity->get('data.itemIds', []),
            'taxonomyIds' => $entity->get('data.taxonomyIds', []),
            'sortBy' => $entity->get('data.sortBy', ''),
            'maxNumberOfItems' => $entity->get('data.limit', 3),
            'locale' => $entity->locale(),
            'localeFallbacks' => $entity->localeFallbacks(),
        ]);
    }

    /**
     * Applies locale to repository if supported.
     *
     * @param null|RepositoryInterface $repository
     * @param null|string $locale
     * @return null|RepositoryInterface
     */    
    protected function withLocaleIfSupported(
        null|RepositoryInterface $repository,
        null|string $locale
    ): null|RepositoryInterface {
        if (
            $locale
            && $repository instanceof LocalesAware
            && $repository->getLocale() !== $locale
        ) {
            return $repository->withLocale($locale);
        }

        return $repository;
    }
    
    /**
     * Get a service from the container.
     *
     * @param null|string $name
     * @return mixed
     */
    protected function getService(null|string $name): mixed
    {
        if (is_null($name)) {
            return null;
        }
        
        return app()->get($name);
    }
}