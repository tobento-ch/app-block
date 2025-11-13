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
 
namespace Tobento\App\Block\Test;

use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Http\Exception\HttpException;

class Configurator implements ConfiguratorInterface
{
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
        if ($for === 'new' && $options['position'] === 'resource') {
            return $blocks->only('text');
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
        if ($entity->position() === 'resource') {
            unset($buttons['delete']);
            return $buttons;
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
        $entity = $action->entity();
        
        if (
            in_array($action->name(), ['store', 'edit', 'update', 'delete'])
            && $entity->get('position') === 'resource'
        ) {
            throw new HttpException(statusCode: 403, message: 'blocks restricted');
        }
        
        return $fields;
    }
    
    /**
     * Configure create block.
     *
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    public function configureCreateBlock(array $block): array
    {
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
        return $entity;
    }
}