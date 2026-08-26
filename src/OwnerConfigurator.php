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
 
namespace Tobento\App\Block;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Http\Exception\HttpException;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

/**
 * Composite configurator that delegates to the first configurator
 * claiming ownership of a block entity.
 */
class OwnerConfigurator implements ConfiguratorInterface
{
    /**
     * @var array<array-key, ConfiguratorInterface>
     */
    protected array $configurators = [];
    
    /**
     * Create a new OwnerConfigurator instance.
     *
     * @param ConfiguratorInterface ...$configurators
     */
    public function __construct(ConfiguratorInterface ...$configurators)
    {
        $this->configurators = $configurators;
    }
    
    /**
     * Register a configurator to participate in ownership-based dispatch.
     *
     * @param ConfiguratorInterface $configurator The configurator to add.
     * @return static Returns the instance for method chaining.
     */
    public function registerConfigurator(ConfiguratorInterface $configurator): static
    {
        $this->configurators[] = $configurator;
        return $this;
    }

    /**
     * Configure editable blocks.
     *
     * @param string $for
     * @param EditableBlocksInterface $blocks
     * @param array<string, mixed> $options
     * @return EditableBlocksInterface
     */
    public function configureEditableBlocks(string $for, EditableBlocksInterface $blocks, array $options): EditableBlocksInterface
    {
        $entity = new BlockEntity($options);

        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface && $configurator->owns($entity)) {
                return $configurator->configureEditableBlocks($for, $blocks, $options);
            }
        }

        return $blocks;
    }

    /**
     * Configure editable block buttons.
     *
     * @param array<string, string> $buttons
     * @param BlockEntityInterface $entity
     * @return array<string, string>
     */
    public function configureEditableBlockButtons(array $buttons, BlockEntityInterface $entity): array
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface && $configurator->owns($entity)) {
                return $configurator->configureEditableBlockButtons($buttons, $entity);
            }
        }

        return $buttons;
    }

    /**
     * Configure action fields.
     *
     * @param ActionInterface $action
     * @param FieldsInterface $fields
     * @return FieldsInterface
     * @throws HttpException
     */
    public function configureActionFields(ActionInterface $action, FieldsInterface $fields): FieldsInterface
    {
        $entity = new BlockEntity($action->entity()->toArray());

        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface && $configurator->owns($entity)) {
                return $configurator->configureActionFields($action, $fields);
            }
        }

        $message = match ($action->name()) {
            'store' => trans('You don\'t have permission to create this block.'),
            'edit', 'update' => trans('You don\'t have permission to edit this block.'),
            'delete' => trans('You don\'t have permission to delete this block.'),
            default => trans('You don\'t have permission to access this block.'),
        };
        
        throw new HttpException(403, $message);
    }
    
    /**
     * Configure reorder block.
     *
     * Called before a block's sortorder is updated.
     * Allows configurators to validate or deny reorder operations.
     *
     * @param BlockEntityInterface $entity
     * @return BlockEntityInterface
     * @throws HttpException
     */
    public function configureReorderBlock(BlockEntityInterface $entity): BlockEntityInterface
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface  && $configurator->owns($entity)) {
                return $configurator->configureReorderBlock($entity);
            }
        }

        throw new HttpException(
            403,
            trans('You don\'t have permission to reorder this block.')
        );
    }

    /**
     * Configure create block.
     *
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    public function configureCreateBlock(array $block): array
    {
        $entity = new BlockEntity($block);

        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface && $configurator->owns($entity)) {
                return $configurator->configureCreateBlock($block);
            }
        }

        return $block;
    }

    /**
     * Configure create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockEntityInterface
     */
    public function configureCreateBlockFromEntity(BlockEntityInterface $entity): BlockEntityInterface
    {
        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface && $configurator->owns($entity)) {
                return $configurator->configureCreateBlockFromEntity($entity);
            }
        }

        return $entity;
    }
}