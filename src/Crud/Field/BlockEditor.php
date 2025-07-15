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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\AbstractField;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\View\ViewInterface;

/**
 * BlockEditor
 */
class BlockEditor extends AbstractField
{
    /**
     * @var string
     */
    protected string $editorName = 'default';
    
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
        //$this->process('index', [$this, 'processIndexAction']);
        $this->process('create|edit|copy', [$this, 'processCreateEdit']);
        //$this->process('show', [$this, 'processShowAction']);
        $this->process('store|update', [$this, 'processSave']);
        $this->process('delete', [$this, 'processDelete']);
        $this->configure();
    }

    /**
     * Create a new instance.
     *
     * @param string $name
     * @param null|string $label
     * @return static
     */
    public static function new(string $name, null|string $label = null): static
    {
        return new static($name, $label);
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
     * Processes the create and edit action.
     *
     * @param ActionInterface $action
     * @param BlockEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreateEdit(
        ActionInterface $action,
        BlockEditor $field,
        ViewInterface $view,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $attributes = $field->getAttributes();
        $blocks = $field->entity()->get($field->name(), []);
        
        foreach($blocks as $index => $block) {
            $blocks[$index] = $editor->getBlockRepository()->createEntity($block);
        }
        
        usort($blocks, fn($a, $b) => $a->sortorder() <=> $b->sortorder());
        
        $field->html($view->render(
            view: 'block/crud/field/block-editor',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'attributes' => $attributes,
                'editor' => $editors->get($field->getEditorName()),
                'blocks' => $blocks,
            ],
        ));
    }
    
    /**
     * Processes the save action.
     *
     * @param ActionInterface $action
     * @param BlockEditor $field
     * @param InputInterface $input
     * @param EditorsInterface $editors
     * @return void
     */
    public function processSave(
        ActionInterface $action,
        BlockEditor $field,
        InputInterface $input,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        
        try {
            $inputBlocks = json_decode($input->get($field->name(), ''), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $inputBlocks = null;
        }
        
        if (!is_array($inputBlocks)) {
            $inputBlocks = [];
        }
        
        $updatedBlocks = [];
        
        // We set the block active and use the updated block to create the block from as it is validated.
        foreach($inputBlocks as $block) {
            $blockId = $block['id'] ?? null;
            
            if (!is_numeric($blockId)) {
                continue;
            }
            
            try {
                $updatedBlock = $editor->getBlockRepository()->updateById(id: (string)$blockId, attributes: [
                    'status' => 'active',
                ]);
                
                $updatedBlocks[] = $updatedBlock->toArray();
            } catch (Throwable $e) {
                //
            }
        }
        
        $input->set($field->name(), $updatedBlocks);
    }
    
    /**
     * Processes the delete action.
     *
     * @param ActionInterface $action
     * @param BlockEditor $field
     * @param InputInterface $input
     * @param EditorsInterface $editors
     * @return void
     */
    public function processDelete(
        ActionInterface $action,
        BlockEditor $field,
        InputInterface $input,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $blocks = $field->entity()->get($field->name(), []);

        // We set the block pending so it gets deleted by pruning.
        foreach($blocks as $block) {
            $blockId = $block['id'] ?? null;
            
            if (!is_numeric($blockId)) {
                continue;
            }
            
            try {
                $editor->getBlockRepository()->updateById(id: (string)$blockId, attributes: [
                    'status' => 'pending',
                ]);
            } catch (\Throwable $e) {
                //
            }
        }
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
}