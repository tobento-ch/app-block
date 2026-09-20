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

namespace Tobento\App\Block\Block;

use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Block\Option\OptionsInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Repository Block
 */
class Repository implements BlockInterface
{
    /**
     * @param ViewInterface $view The view renderer.
     * @param OptionsInterface $options The block options.
     * @param null|RepositoryInterface $itemRepository The item repository.
     * @param null|RepositoryInterface $taxonomyRepository The taxonomy repository.
     * @param null|callable $resolveTaxonomyIds Callable resolving taxonomy IDs.
     * @param array $itemIds Selected item IDs.
     * @param array $taxonomyIds Selected taxonomy IDs.
     * @param array $itemBaseWhere Base where conditions for items.
     * @param array $taxonomyBaseWhere Base where conditions for taxonomies.
     * @param array $itemsOrderBy Order-by array for items.
     * @param int $maxNumberOfItems Maximum number of items to fetch.
     * @param string $idName Primary key field name.
     * @param bool $generateImagesInBackground
     * @param null|string $viewName Explicit view name or null.
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsInterface $options,        
        protected null|RepositoryInterface $itemRepository,
        protected null|RepositoryInterface $taxonomyRepository,
        protected $resolveTaxonomyIds = null,
        protected array $itemIds = [],
        protected array $taxonomyIds = [],
        protected array $itemBaseWhere = [],
        protected array $taxonomyBaseWhere = [],
        protected array $itemsOrderBy = [],
        protected int $maxNumberOfItems = 3,
        protected string $idName = 'id',
        protected bool $generateImagesInBackground = true,
        protected null|string $viewName = null,
    ) {}
    
    /**
     * Returns the rendered block content.
     *
     * @return string
     */
    public function render(): string
    {
        if (is_null($this->itemRepository)) {
            return '';
        }
        
        // Base where
        $where = $this->itemBaseWhere;

        // Taxonomy resolution (via injected callable)
        if (
            $this->taxonomyRepository
            && $this->resolveTaxonomyIds
            && !empty($this->taxonomyIds)
        ) {
            $ids = ($this->resolveTaxonomyIds)(
                $this->taxonomyRepository,
                $this->taxonomyIds,
            );

            $this->itemIds = array_unique(array_merge($this->itemIds, $ids));
        }

        // Item ID filtering
        if (!empty($this->itemIds)) {
            $where[$this->idName] = ['in' => $this->itemIds];
        }

        // Fetch items
        $items = $this->itemRepository->findAll(
            where: $where,
            limit: min($this->maxNumberOfItems, 1000),
            orderBy: $this->itemsOrderBy,
        );

        // Render
        $view = $this->viewName ?: 'block/repository';

        return $this->view->render(view: $view, data: [
            'block' => $this,
            'items' => $items,
            'generateImagesInBackground' => $this->generateImagesInBackground,
        ]);
    }

    /**
     * Returns the options.
     *
     * @return OptionsInterface
     */
    public function options(): OptionsInterface
    {
        return $this->options;
    }
    
    public function itemsOrderBy(): array
    {
        return $this->itemsOrderBy;
    }

    public function resolveTaxonomyIds(): ?callable
    {
        return $this->resolveTaxonomyIds;
    }

    public function viewName(): ?string
    {
        return $this->viewName;
    }

    public function maxNumberOfItems(): int
    {
        return $this->maxNumberOfItems;
    }

    public function itemIds(): array
    {
        return $this->itemIds;
    }

    public function taxonomyIds(): array
    {
        return $this->taxonomyIds;
    }

    public function generateImagesInBackground(): bool
    {
        return $this->generateImagesInBackground;
    }
}