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

namespace Tobento\App\Block\Crud\Field;

use JsonException;
use Throwable;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Resource;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\AbstractField;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;

/**
 * BlockResourceEditor
 */
class BlockResourceEditor extends AbstractField
{
    /**
     * @var string
     */
    protected string $editorName = 'default';

    /**
     * @var array<array-key, string>
     */
    protected array $blockPositions = ['resource'];
    
    /**
     * @var string|callable
     */
    protected $resourceId = '';
    
    /**
     * @var string
     */
    protected string $resourceGroup = '';
    
    /**
     * Create a new BlockEditor.
     *
     * @param string $name
     * @param null|string $label
     */
    final public function __construct(
        string $name,
        null|string $label = null,
    ) {
        $this->name = $name;
        $this->label = $label;
        //$this->process('edit', [$this, 'processCreateEdit']);
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        $this->process('store|update', [$this, 'processSave']);
        $this->process('stored', [$this, 'processStored']);
        $this->process('delete', [$this, 'processDelete']);
        $this->storable(false);
        $this->editable(false);
        $this->configure();
    }
    
    /**
     * Sets the editor.
     *
     * @param string $name
     * @return static $this
     */
    public function editor(string $name): static
    {
        $this->editorName = $name;
        return $this;
    }
    
    /**
     * Returns the editor name.
     *
     * @return string
     */
    public function getEditorName(): string
    {
        return $this->editorName;
    }
    
    /**
     * Sets the resource id.
     *
     * @param string|callable $id
     * @return static $this
     */
    public function resourceId(string|callable $id): static
    {
        $this->resourceId = $id;
        return $this;
    }
    
    /**
     * Returns the resource id.
     *
     * @return string|callable
     */
    public function getResourceId(): string|callable
    {
        return $this->resourceId;
    }
    
    /**
     * Sets the resource id.
     *
     * @param string $group
     * @return static $this
     */
    public function resourceGroup(string $group): static
    {
        $this->resourceGroup = $group;
        return $this;
    }
    
    /**
     * Returns the resource group.
     *
     * @return string
     */
    public function getResourceGroup(): string
    {
        return $this->resourceGroup;
    }
    
    /**
     * Sets the block position(s).
     *
     * @param string ...$position
     * @return static $this
     */
    public function blockPositions(string ...$position): static
    {
        $this->blockPositions = $position;
        return $this;
    }
    
    /**
     * Returns the block positions.
     *
     * @return array<array-key, string>
     */
    public function getBlockPositions(): array
    {
        return $this->blockPositions;
    }
    
    /**
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        BlockResourceEditor $field,
        ViewInterface $view,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $attributes = $field->getAttributes();
        $resourceId = $this->resolveResourceId($action, $field);
        
        $blocks = $editor->getBlockRepository()->findAllByResource(
            new Resource(id: $resourceId, group: $field->getResourceGroup())
        );
        
        // Blocks by position:
        $blocksByPosition = [];
        
        foreach($blocks as $entity) {
            $blocksByPosition[$entity->position()][] = $entity;
        }
        
        $field->html($view->render(
            view: 'block/crud/field/block-resource',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'attributes' => $attributes,
                'editor' => $editors->get($field->getEditorName()),
                'blocksByPosition' => $blocksByPosition,
                'positions' => $field->getBlockPositions(),
            ],
        ));
    }
    
    /**
     * Processes the save action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param InputInterface $input
     * @param EditorsInterface $editors
     * @return void
     */
    public function processSave(
        ActionInterface $action,
        BlockResourceEditor $field,
        InputInterface $input,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $inputBlocks = [];
                
        foreach($input->get($field->name(), []) as $blocks) {
            if (!is_string($blocks)) {
                continue;
            }
            
            try {
                $blocks = json_decode($blocks, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $blocks = null;
            }
            
            if (is_array($blocks)) {
                $inputBlocks = array_merge($inputBlocks, $blocks);
            }
        }
        
        $updatedBlocks = [];
        
        // We set the block active and use the updated block to create the block from as it is validated.
        foreach($inputBlocks as $inputBlock) {
            $blockId = $inputBlock['id'] ?? null;
            
            if (!is_numeric($blockId)) {
                continue;
            }
            
            try {
                $updatedBlock = $editor->getBlockRepository()->updateById(id: (string)$blockId, attributes: [
                    'status' => 'active',
                ]);
                
                // only store resource positioned blocks:
                if (str_starts_with($updatedBlock->position(), 'resource')) {
                    $updatedBlocks[] = $updatedBlock->toArray();
                }
            } catch (Throwable $e) {
                //
            }
        }
        
        $input->set($field->name(), $updatedBlocks);
    }
    
    /**
     * Processes the stored action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param EditorsInterface $editors
     * @return void
     */
    public function processStored(
        ActionInterface $action,
        BlockResourceEditor $field,
        InputInterface $input,
        EditorsInterface $editors,
    ): void {
        $blocks = $input->get($field->name(), []);
        
        if (empty($blocks)) {
            return;
        }
        
        $tmpResourceId = null;
        
        foreach($blocks as $block) {
            $tmpResourceId = $block['resource_id'] ?? null;
            break;
        }
        
        if (empty($tmpResourceId)) {
            return;
        }
        
        $resourceId = $this->resolveResourceId($action, $field);
        $editor = $editors->get($field->getEditorName());
        
        try {
            $editor->getBlockRepository()->update(
                where: [
                    'resource_id' => $tmpResourceId,
                    'resource_group' => $field->getResourceGroup(),
                ],
                attributes: [
                    'resource_id' => $resourceId,
                ]
            );
        } catch (\Throwable $e) {
            //
        }
    }
    
    /**
     * Processes the delete action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param InputInterface $input
     * @param EditorsInterface $editors
     * @return void
     */
    public function processDelete(
        ActionInterface $action,
        BlockResourceEditor $field,
        InputInterface $input,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $resourceId = $this->resolveResourceId($action, $field);
        
        // We set the block pending so it gets deleted by pruning.
        $editor->getBlockRepository()->update(
            where: [
                'resource_id' => $resourceId,
                'resource_group' => $field->getResourceGroup(),
            ],
            attributes: [
                'status' => 'pending',
            ]
        );
    }
    
    /**
     * Set if the attribute is translatable.
     *
     * @param bool $translatable
     * @return static $this
     */
    public function translatable(bool $translatable = true): static
    {
        throw new \InvalidArgumentException('Field does not support translations');
    }
    
    /**
     * Resolves the resource id.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @return string
     */
    protected function resolveResourceId(
        ActionInterface $action,
        BlockResourceEditor $field,
    ): string {
        $resourceId = $field->getResourceId();
        
        if (in_array($action->name(), ['create', 'copy'])) {
            $resourceId = sprintf('tmp:%s', time());
        }
        
        if (is_callable($resourceId)) {
            $resourceId = call_user_func($resourceId, $field->entity(), $action);
        }
        
        if ($resourceId === '') {
            $resourceId = sprintf('%s:%s', $action->controller()->resourceName(), $field->entity()->id());
        }
        
        $field->resourceId($resourceId);
        
        return is_string($resourceId) ? $resourceId : '';
    }
}