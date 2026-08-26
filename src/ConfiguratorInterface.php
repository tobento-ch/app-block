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

use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Http\Exception\HttpException;

interface ConfiguratorInterface
{
    /**
     * Configure editable blocks.
     *
     * @param string $for
     * @param EditableBlocksInterface $blocks
     * @param array<string, mixed> $options
     * @return EditableBlocksInterface
     */
    public function configureEditableBlocks(string $for, EditableBlocksInterface $blocks, array $options): EditableBlocksInterface;
    
    /**
     * Configure editable block buttons.
     *
     * @param array<string, string> $buttons
     * @param BlockEntityInterface $entity
     * @return array<string, string>
     */
    public function configureEditableBlockButtons(array $buttons, BlockEntityInterface $entity): array;
    
    /**
     * Configure action fields.
     *
     * @param ActionInterface $action
     * @param FieldsInterface $fields
     * @return FieldsInterface
     * @throws HttpException
     */
    public function configureActionFields(ActionInterface $action, FieldsInterface $fields): FieldsInterface;
    
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
    public function configureReorderBlock(BlockEntityInterface $entity): BlockEntityInterface;
    
    /**
     * Configure create block.
     *
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    public function configureCreateBlock(array $block): array;
    
    /**
     * Configure create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockEntityInterface
     */
    public function configureCreateBlockFromEntity(BlockEntityInterface $entity): BlockEntityInterface;
}