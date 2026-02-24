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

use Closure;
use JsonException;
use Throwable;
use Tobento\App\AppInterface;
use Tobento\App\Block\Controller\BlockEditorController;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Resource;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\AbstractField;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\View\ViewInterface;

/**
 * BlockResourceEditor
 */
class BlockResourceEditor extends AbstractField
{
    use \Tobento\App\Logging\LoggerTrait;
    
    /**
     * @var string
     */
    protected string $editorName = 'default';

    /**
     * @var array<array-key, string>
     */
    protected array $blockPositions = ['resource'];
    
    /**
     * @var bool|string|Closure
     */
    protected bool|string|Closure $positionTitle = true;
    
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
        $this->process('create', [$this, 'processCreate']);
        $this->process('edit', [$this, 'processEdit']);
        $this->process('copy', [$this, 'processCopy']);
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
     * Sets the block position title.
     *
     * @param bool|string|Closure $title
     * @return static $this
     */
    public function positionTitle(bool|string|Closure $title): static
    {
        $this->positionTitle = $title;
        return $this;
    }
    
    /**
     * Returns the position title.
     *
     * @param string $position
     * @return null|string
     */
    public function getPositionTitle(string $position): null|string
    {
        return match (true) {
            $this->positionTitle === true => $position,
            $this->positionTitle === false => null,
            is_string($this->positionTitle) => $this->positionTitle,
            is_callable($this->positionTitle) => ($this->positionTitle)($position),
            default => null,
        };
    }

    /**
     * Processes the create action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCreate(
        ActionInterface $action,
        BlockResourceEditor $field,
        ViewInterface $view,
        EditorsInterface $editors,
    ): void {
        $editor = $editors->get($field->getEditorName());
        $attributes = $field->getAttributes();
        
        // Restore block IDs from input (after validation error e.g.)
        $inputBlocks = [];

        foreach ($action->getInput()->get($field->name(), []) as $blocks) {
            if (!is_string($blocks)) {
                continue;
            }

            try {
                $decoded = json_decode($blocks, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                $decoded = null;
            }

            if (is_array($decoded)) {
                $inputBlocks = array_merge($inputBlocks, $decoded);
            }
        }

        $ids = [];

        foreach ($inputBlocks as $inputBlock) {
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
     * Processes the edit action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processEdit(
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
     * Processes the copy action.
     *
     * @param ActionInterface $action
     * @param BlockResourceEditor $field
     * @param ViewInterface $view
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function processCopy(
        ActionInterface $action,
        BlockResourceEditor $field,
        ViewInterface $view,
        EditorsInterface $editors,
    ): void {
        
        if (!empty($action->getInput()->get($field->name(), []))) {
            $this->processCreate(
                action: $action,
                field: $field,
                view: $view,
                editors: $editors,
            );
            return;
        }
        
        $editor = $editors->get($field->getEditorName());
        $repo = $editor->getBlockRepository();
        $attributes = $field->getAttributes();

        // Resolve temporary resource id for COPY
        $resourceId = $this->resolveResourceId($action, $field);
        $resource = new Resource(id: $resourceId, group: $field->getResourceGroup());

        // Load original blocks from resource
        $originalBlocks = $repo->findAllByResource($resource);
        
        $container = $action->container();
        $app = $container->get(AppInterface::class);
        $requester = $container->get(RequesterInterface::class);
        $responser = $container->get(ResponserInterface::class);

        // Prepare controller with editor context
        $blockEditorController = $app->make(BlockEditorController::class, [
            'requester' => new Requester(
                $requester->request()->withQueryParams(['editor' => $editor->name()])
            ),
        ]);

        $blocks = [];
        
        $tmpResourceId = sprintf('tmp:%s', time());
        
        foreach ($originalBlocks as $orig) {

            // only copy resource positioned blocks:
            if (!str_starts_with($orig->position(), 'resource')) {
                continue;
            }
            
            // Convert entity to array
            $block = $orig->toArray();

            unset($block['id'], $block['created_at']);
            $block['status'] = 'pending';
            $block['resource_id'] = $tmpResourceId;
            $block['position'] = $orig->position();

            // Fake POST request
            $request = $requester->request()
                ->withMethod('POST')
                ->withParsedBody(['block' => $block]);

            try {
                $response = $app->call(
                    [$blockEditorController, 'storeBlock'],
                    [
                        'requester' => new Requester($request),
                        'responser' => $responser,
                    ]
                );

                $payload = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $blocks[] = $repo->createEntity($payload['block']);

            } catch (Throwable $e) {
                $this->getLogger()->error(
                    message: 'Failed to copy resource block',
                    context: ['block' => $block, 'exception' => $e],
                );
                continue;
            }
        }

        // Sort blocks for UI
        usort($blocks, fn($a, $b) => $a->sortorder() <=> $b->sortorder());

        // Group blocks by position (same as processCreateEdit)
        $blocksByPosition = [];
        foreach ($blocks as $entity) {
            $blocksByPosition[$entity->position()][] = $entity;
        }

        // Render editor
        $field->html($view->render(
            view: 'block/crud/field/block-resource',
            data: [
                'field' => $field,
                'entity' => $field->entity(),
                'actionName' => $action->name(),
                'attributes' => $attributes,
                'editor' => $editor,
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
        
        if (in_array($action->name(), ['create'])) {
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