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

namespace Tobento\App\Block\Editable;

use Tobento\App\Block\Editable\Option\OptionsInterface;
use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

/**
 * Base class for editable blocks that list items from a repository.
 *
 * Provides common fields such as sorting, limiting, taxonomy selection
 * (optional), and item selection. Subclasses only define repository
 * specifics and block metadata.
 */
abstract class AbstractRepository implements EditableBlockInterface
{
    /**
     * @var array<int, string> Names of default fields to include.
     */
    protected array $defaultFieldNames = [
        'sortBy', 'limit', 'taxonomy', 'items',
    ];

    /**
     * Create a new instance.
     *
     * @param OptionsInterface $options
     */
    public function __construct(
        protected OptionsInterface $options,
    ) {}

    /**
     * Returns the block type identifier.
     *
     * @return string
     */
    abstract public function type(): string;

    /**
     * Returns the block title.
     *
     * @return string
     */
    abstract public function title(): string;

    /**
     * Returns the block description.
     *
     * @return string
     */
    abstract public function description(): string;

    /**
     * Returns the block icon HTML.
     *
     * @return string
     */
    abstract public function icon(): string;

    /**
     * Returns the repository class used to fetch items.
     *
     * @return class-string
     */
    abstract protected function itemRepository(): string;
    
    /**
     * Returns base where conditions for item repository queries.
     *
     * @return array<string, mixed>
     */
    abstract protected function itemBaseWhere(): array;
    
    /**
     * Converts a repository item to an option.
     *
     * @param object $item
     * @return Field\Option
     */
    abstract protected function itemToOption(object $item): Field\Option;

    /**
     * Returns the taxonomy repository class or null if not used.
     *
     * @return null|string
     */
    abstract protected function taxonomyRepository(): null|string;

    /**
     * Returns base where conditions for taxonomy repository queries.
     *
     * @return array<string, mixed>
     */
    abstract protected function taxonomyBaseWhere(): array;
    
    /**
     * Converts a repository taxonomy item to an option.
     *
     * @param object $item
     * @return Field\Option
     */
    abstract protected function taxonomyItemToOption(object $item): Field\Option;

    /**
     * Enables default fields by name.
     *
     * @param string ...$names
     * @return static
     */
    public function withDefaultFields(string ...$names): static
    {
        $clone = clone $this;
        $clone->defaultFieldNames = $names;
        return $clone;
    }

    /**
     * Returns the default block data.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => $this->type()];
    }

    /**
     * Configures the CRUD fields for the block.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        if (in_array('sortBy', $this->defaultFieldNames)) {
            yield new Field\Select(name: 'data.sortBy', label: trans('Sort by'))
                ->group($this->title())
                ->options([
                    'date' => trans('Date'),
                    'title' => trans('Title'),
                ])
                ->emptyOption('none', '---');
        }

        if (in_array('limit', $this->defaultFieldNames)) {
            yield new Field\Text(name: 'data.limit', label: trans('Show max'))
                ->group($this->title())
                ->type('number')
                ->defaultValue('3')
                ->validate('minNum:1|maxNum:100');
        }

        if (
            in_array('taxonomy', $this->defaultFieldNames)
            && $this->taxonomyRepository()
        ) {
            yield new Field\Options(name: 'data.taxonomyIds', label: trans('Categories'))
                ->group($this->title())
                ->repository($this->taxonomyRepository())
                ->baseWhere($this->taxonomyBaseWhere())
                ->toOption(fn(object $item) => $this->taxonomyItemToOption($item))
                ->placeholder(trans('Search Categories'))
                ->limit(12);
        }

        if (in_array('items', $this->defaultFieldNames)) {
            yield new Field\Options(name: 'data.itemIds', label: trans('Items'))
                ->group($this->title())
                ->repository($this->itemRepository())
                ->baseWhere($this->itemBaseWhere())
                ->toOption(fn(object $item) => $this->itemToOption($item))
                ->placeholder(trans('Search items'))
                ->limit(12);
        }

        // Append block option fields
        yield from $this->options->configureFields($action, $this);
    }

    /**
     * Maps block data to CRUD fields.
     *
     * @param array<string, mixed> $block
     * @param ActionInterface $action
     * @return array<string, mixed>
     */
    public function toFields(array $block, ActionInterface $action): array
    {
        return $block;
    }
}