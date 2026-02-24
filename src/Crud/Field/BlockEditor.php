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
use Tobento\App\AppInterface;
use Tobento\App\Block\Controller\BlockEditorController;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\AbstractField;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;

/**
 * BlockEditor
 */
class BlockEditor extends AbstractField
{
    use \Tobento\App\Logging\LoggerTrait;
    
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
        $this->process('create', [$this, 'processCreate']);
        $this->process('edit', [$this, 'processEdit']);
        $this->process('copy', [$this, 'processCopy']);
        //$this->process('show', [$this, 'processShowAction']);
        $this->process('store|update', [$this, 'processSave']);
        $this->process('delete', [$this, 'processDelete']);
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
     * Processes the create action.
     *
     * @param ActionInterface $action
     * @param BlockEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreate(
        ActionInterface $action,
        BlockEditor $field,
        ViewInterface $view,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $attributes = $field->getAttributes();
        
        // Restore block IDs from input (after validation error e.g.)
        try {
            $inputBlocks = json_decode($action->getInput()->get($field->name(), ''), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $inputBlocks = [];
        }
        
        $ids = [];
        
        foreach($inputBlocks as $inputBlock) {
            if (isset($inputBlock['id']) && is_numeric($inputBlock['id'])) {
                $ids[] = (string) $inputBlock['id'];
            }
        }
        
        $blocks = [];
        
        if (!empty($ids)) {
            /** @var \Tobento\Service\Storage\ItemsInterface $items */
            $items = $editor->getBlockRepository()->findByIds(...$ids);
            $blocks = $items->all();
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
     * Processes the edit action.
     *
     * @param ActionInterface $action
     * @param BlockEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processEdit(
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
     * Processes the copy action.
     *
     * @param ActionInterface $action
     * @param BlockEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCopy(
        ActionInterface $action,
        BlockEditor $field,
        ViewInterface $view,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $attributes = $field->getAttributes();
        $repo = $editor->getBlockRepository();
        
        // 1. Try restoring from hidden input (after validation error)
        try {
            $inputBlocks = json_decode($action->getInput()->get($field->name(), ''), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $inputBlocks = [];
        }

        $ids = [];

        foreach ($inputBlocks as $inputBlock) {
            if (isset($inputBlock['id']) && is_numeric($inputBlock['id'])) {
                $ids[] = (string) $inputBlock['id'];
            }
        }

        $blocks = [];

        if (!empty($ids)) {
            // 2. Restore blocks from DB (safe, validated)
            /** @var \Tobento\Service\Storage\ItemsInterface $items */
            $items = $repo->findByIds(...$ids);
            $blocks = $items->all();
        } else {
            // 3. First load copy original blocks into new pending blocks
            $originalBlocks = $field->entity()->get($field->name(), []);
            
            $container = $action->container();
            $app = $container->get(AppInterface::class);
            $requester = $container->get(RequesterInterface::class);
            $responser = $container->get(ResponserInterface::class);

            $blockEditorController = $app->make(BlockEditorController::class, [
                'requester' => new Requester(
                    $requester->request()->withQueryParams(['editor' => $editor->name()])
                ),
            ]);

            foreach ($originalBlocks as $block) {

                unset($block['id'], $block['created_at']);
                $block['status'] = 'pending';
                
                // Create a fake request for the block
                $request = $requester->request()
                    ->withMethod('POST')
                    ->withParsedBody(['block' => $block]);
                
                // Call the storeBlock() action
                try {
                    $response = $app->call(
                        [$blockEditorController, 'storeBlock'],
                        [
                            'requester' => new Requester($request),
                            'responser' => $responser,
                        ]
                    );
                    
                    // Extract the created block from the JSON response
                    $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    $blocks[] = $editor->getBlockRepository()->createEntity($payload['block']);
                } catch (Throwable $e) {
                    $this->getLogger()->error(
                        message: 'Failed to copy block',
                        context: ['block' => $block, 'exception' => $e],
                    );
                    continue;
                }
            }
        }

        // 4. Sort blocks for consistent UI
        usort($blocks, fn($a, $b) => $a->sortorder() <=> $b->sortorder());

        // 5. Render editor
        $field->html($view->render(
            view: 'block/crud/field/block-editor',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'attributes' => $attributes,
                'editor' => $editor,
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