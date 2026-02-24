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
 
namespace Tobento\App\Block\Controller;

use JsonException;
use Psr\Clock\ClockInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Entity\Entity;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Exception\ActionNotFoundException;
use Tobento\App\Crud\Exception\EntityNotFoundException;
use Tobento\App\Crud\Exception\EntityUndeletableException;
use Tobento\App\Crud\Exception\EntityUnupdatableException;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\ParentFieldsAwareInterface;
use Tobento\App\Crud\Filter;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Input\Input;
use Tobento\App\Crud\Input\InputInterface;
use Tobento\App\Http\Exception\HttpException;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

class BlockEditorController extends AbstractCrudController
{
    /**
     * @var EditorInterface $editor
     */
    protected EditorInterface $editor;
    
    /**
     * Must be unique, lowercase and only of [a-z-] characters.
     */
    public const RESOURCE_NAME = 'block-editor';
    
    /**
     * Create a new BlockEditorController instance.
     *
     * @param EditorsInterface $editors
     * @param RequesterInterface $requester
     * @param TranslatorInterface $translator
     * @param RouterInterface $router
     */
    public function __construct(
        EditorsInterface $editors,
        RequesterInterface $requester,
        protected TranslatorInterface $translator,
        protected RouterInterface $router,
        $editorName = '',
    ) {
        $editorName = $requester->input()->get('editor', $editorName);

        if (! $editors->has($editorName)) {
            throw new HttpException(
                statusCode: 403,
                message: $translator->trans('Editor :name not found.', [':name' => $editorName])
            );
        }
        
        $this->editor = $editors->get($editorName);
        $this->repository = $this->editor()->getBlockRepository();
    }
    
    /**
     * Returns the editor.
     *
     * @return EditorInterface
     */
    public function editor(): EditorInterface
    {
        return $this->editor;
    }
    
    /**
     * Create entity from array.
     *
     * @param array $data
     * @return EntityInterface
     */
    public function createEntityFromArray(array $data): EntityInterface
    {
        return new Entity(
            attributes: $data,
            idAttributeName: $this->entityIdName(),
        );
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        if  ($action->name() === 'store') {
            $resourceId = $action->getInput()->get('resource_id');
            $position = $action->getInput()->get('position', '');
            
            // set resource id only if position is starting with "resource"!
            if (!str_starts_with($position, 'resource')) {
                $resourceId = null;
            }
        } else {
            $resourceId = $action->entity()->get('resource_id');
        }
        
        $blockType = $action->getInput()->get('type', '');

        if (! $this->editor()->getEditableBlocks()->has($blockType)) {
            throw new HttpException(
                statusCode: 403,
                message: $this->translator->trans('Editable block :type not found.', [':type' => $blockType])
            );
        }

        $block = $this->editor()->getEditableBlocks()->get($blockType);
        $blockFields = $block->configureFields($action);
        
        if (count($this->editor()->locales()) > 1) {
            yield new Field\Select('locale', $this->translator->trans('Language to display'))
                ->group($this->translator->trans('General'))
                ->selected(value: $this->editor()->locale(), action: 'create')
                ->options($this->editor()->locales());            
        }
        
        foreach($blockFields as $blockField) {
            yield $blockField;
        }
        
        if (count($this->editor()->locales()) <= 1) {
            yield new Field\Text('locale')
                ->group('hidden')
                ->type('hidden')
                ->value($this->editor()->locale())
                ->validate(sprintf('in:%s', $this->editor()->locale()));
        }
        
        yield new Field\PrimaryId('id')
            ->group('hidden')
            ->type('hidden')
            ->editable()
            ->validate('numeric|minNum:1|maxLen:1000');
        yield new Field\Value(name: 'type')
            ->group('hidden')
            ->editable(false)
            ->value($blockType);
        yield new Field\Value(name: 'editor')
            ->group('hidden')
            ->editable(false)
            ->value($this->editor()->name());
        yield new Field\Value(name: 'resource_id')
            ->group('hidden')
            ->editable(true)
            ->value($resourceId)
            ->validate('string|htmlclean|maxLen:200');
        yield new Field\Text(name: 'resource_group')
            ->group('hidden')
            ->type('hidden')
            ->validate('string|htmlclean|maxLen:200');
        yield new Field\Text(name: 'position')
            ->group('hidden')
            ->type('hidden')
            ->validate('string|htmlclean|maxLen:100');
        yield new Field\Text('sortorder')
            ->group('hidden')
            ->type('hidden')
            ->defaultValue('0')
            ->validate('numeric|minNum:0|maxLen:6');
        yield new Field\Text(name: 'status')
            ->group('hidden')
            ->type('hidden')
            ->validate('string|alpha|maxLen:100');
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        return [
            new Action\Store(),
            new Action\Edit()
                ->view('block/crud/edit'),
            new Action\Update()
                ->url((string)$this->router->url('block-editor.update.block')),
            new Action\Delete(),
        ];
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [];
    }
    
    /**
     * Returns the edit response.
     *
     * @param int|string $id
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function edit(
        int|string $id,
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        throw new ActionNotFoundException(actionName: 'edit');
    }
    
    /**
     * Returns the store response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function storeBlock(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $block = $requester->input()->get('block', []);
        $blockType = $block['type'] ?? '';
        $blockType = is_string($blockType) ? $blockType : '';
        
        if (! $this->editor->getEditableBlocks()->has($blockType)) {
            throw new HttpException(
                statusCode: 422,
                message: $this->translator->trans('Editable block :type not found.', [':type' => $blockType])
            );
        }
        
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'store');
        
        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'store');
        }

        $editableBlock = $this->editor()->getEditableBlocks()->get($blockType);
        $block = $editableBlock->toFields($block, $action);
        
        $request = $requester->request()->withParsedBody($block);
        $requester = new Requester($request);
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        $action->locales($this->editor()->locales());
        $entity = $this->createEntityFromArray($block);
        $action->setEntity($entity);
                
        // Handle input:
        $action->setInput(new Input(
            array_replace_recursive($requester->input()->all(), $requester->request()->getUploadedFiles())
        ));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $fields = $action->fields()->creatable();

        $fields = $this->editor()->getConfigurator()->configureActionFields(action: $action, fields: $fields);

        $action->setFields($fields);
        
        // Process action:
        $actionProcessor->processAction(action: $action);
        
        // Create entity:
        $attributes = $action->getInput()
            ->collection()
            ->onlyPresent($action->fields()->storable()->getNames())
            ->all();
        
        $repositoryEntity = $this->storeEntity($attributes);
        $entity = $this->createEntityFromObject($repositoryEntity);
        
        // Process stored fields action:
        $actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'stored',
            entity: $entity,
        );
        
        $block = $this->editor()->getBlockFactory()->createBlockFromEntity($repositoryEntity);
        
        return $responser->json([
            'status' => 200,
            'block' => $repositoryEntity->toArray(),
            'html' => $block->render(),
        ]);
    }
    
    /**
     * Returns the edit response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function editBlock(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $block = $requester->input()->get('block', []);
        $blockType = $block['type'] ?? '';
        $blockType = is_string($blockType) ? $blockType : '';
        
        if (! $this->editor()->getEditableBlocks()->has($blockType)) {
            throw new HttpException(
                statusCode: 403,
                message: $this->translator->trans('Editable block :type not found.', [':type' => $blockType])
            );
        }
        
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'edit');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'edit');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        $action->locales($this->editor()->locales());
        $entity = $this->createEntityFromArray($block);
        $inputValues = $requester->input()->all();
        unset($inputValues['block']);
        $request = $requester->request()->withParsedBody(array_merge($inputValues, $block));
        $requester = new Requester($request);
        $action->setEntity($entity);
        
        // Handle input:
        $action->setInput(new Input($requester->input()->all()));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $fields = $action->fields()->editable();
        
        $fields = $this->editor()->getConfigurator()->configureActionFields(action: $action, fields: $fields);
        
        $action->setFields($fields);
        
        // Process action:
        $actionProcessor->processAction(action: $action);
        
        return $responser->render(
            view: $action->getView(),
            data: [
                'action' => $action->setFields($action->fields()->parent(null)),
            ],
        );
    }

    /**
     * Returns the update response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function updateBlock(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        try {
            $block = json_decode($requester->input()->get('block', ''), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $block = [];
        }
        
        $blockType = $block['type'] ?? '';
        $blockType = is_string($blockType) ? $blockType : '';
        
        if (! $this->editor()->getEditableBlocks()->has($blockType)) {
            throw new HttpException(
                statusCode: 422,
                message: $this->translator->trans('Editable block :type not found.', [':type' => $blockType])
            );
        }
        
        $editableBlock = $this->editor()->getEditableBlocks()->get($blockType);
        $blockId = $block['id'] ?? null;
        $blockId = is_int($blockId) || is_string($blockId) ? $blockId : 0;
        
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'update');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'update');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        $action->locales($this->editor()->locales());
        
        // Handle entity:
        $entity = $this->repository()->findById($blockId);
        
        if ($entity === null) {
            throw new EntityNotFoundException($blockId, $action);
        }
        
        $action->setEntity($this->createEntityFromObject($entity));
        
        // Check if entity can be updated:
        if ($action instanceof Action\Update && ! $action->isUpdatable($action->entity())) {
            throw new EntityUnupdatableException($blockId, $action);
        }
        
        $block = $editableBlock->toFields($block, $action);
        
        // Handle input:
        $action->setInput(new Input(
            array_replace_recursive($block, $requester->input()->all(), $requester->request()->getUploadedFiles())
        ));
        
        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $fields = $action->fields()->editable();
        
        $fields = $this->editor()->getConfigurator()->configureActionFields(action: $action, fields: $fields);
        
        if ($requester->isAjax()) {
            $inputKeys = array_merge(array_keys($block), $requester->input()->keys()->all());
            $inputKeys = array_merge($inputKeys, array_keys($requester->request()->getUploadedFiles()));
            $fields = $fields->filter(
                fn (FieldInterface $f): bool
                => $f instanceof ParentFieldsAwareInterface || in_array(explode('.', $f->name())[0], $inputKeys)
            );
        }

        $action->setFields($fields);
        
        // Process action:
        $actionProcessor->processAction(action: $action);

        // Update entity:
        $attributes = $action->getInput()
            ->collection()
            ->onlyPresent($action->fields()->storable()->getNames())
            ->all();
        
        $updatedItem = $this->updateEntity($blockId, $attributes, $action->entity());
        $entity = $this->createEntityFromObject($updatedItem);
        
        // Process updated fields action:
        $actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'updated',
            entity: $entity,
        );
        
        $block = $this->editor()->getBlockFactory()->createBlockFromEntity($updatedItem);

        return $responser->json([
            'status' => 200,
            'block' => $updatedItem->toArray(),
            'html' => $block->render(),
        ]);
    }
    
    /**
     * Returns the delete response.
     *
     * @param ActionProcessorInterface $actionProcessor
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function deleteBlock(
        ActionProcessorInterface $actionProcessor,
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $block = $requester->input()->get('block', []);
        $blockType = $block['type'] ?? '';
        $blockType = is_string($blockType) ? $blockType : '';

        if (! $this->editor()->getEditableBlocks()->has($blockType)) {
            throw new HttpException(
                statusCode: 422,
                message: $this->translator->trans('Editable block :type not found.', [':type' => $blockType])
            );
        }
        
        // Get the action:
        $actions = $this->getConfiguredActions();
        $action = $actions->get(name: 'delete');

        if (is_null($action)) {
            throw new ActionNotFoundException(actionName: 'delete');
        }
        
        $action->setController($this);
        $action->setActions($actions);
        $actionProcessor->preprocessAction(action: $action);
        $action->locales($this->editor()->locales());        
        
        $editableBlock = $this->editor()->getEditableBlocks()->get($blockType);
        $blockFields = $editableBlock->toFields($block, $action);
        $request = $requester->request()->withParsedBody($blockFields);
        $requester = new Requester($request);
        $blockId = $block['id'] ?? 0;
        $blockId = is_int($blockId) || is_string($blockId) ? $blockId : 0;
        
        // Handle entity:
        $entity = $this->repository()->findById($blockId);
                
        if ($entity === null) {
            throw new EntityNotFoundException($blockId, $action);
        }
        
        $action->setEntity($this->createEntityFromObject($entity));
        
        // Check if entity can be deleted:
        if ($action instanceof Action\Delete && ! $action->isDeletable($action->entity())) {
            throw new EntityUndeletableException($blockId, $action);
        }
        
        // Handle input:
        $action->setInput(new Input($requester->input()->all()));

        // Set the configured fields if none specified:
        if ($action->fields()->empty()) {
            $action->setFields($this->getConfiguredFields(action: $action));
        }
        
        $fields = $this->editor()->getConfigurator()->configureActionFields(action: $action, fields: $action->fields());
        
        $action->setFields($fields);
        
        // Process action:
        $actionProcessor->processAction(action: $action);
        
        // Delete entity:
        $this->deleteEntity(id: $blockId, entity: $action->entity());
        
        // Process deleted fields action:
        $actionProcessor->processFieldsAction(
            action: $action,
            actionName: 'deleted',
        );
        
        return $responser->json([
            'status' => 200,
            'block' => $entity->toArray(),
        ]);
    }
    
    /**
     * Returns the create response.
     *
     * @param RequesterInterface $requester
     * @param ResponserInterface $responser
     * @return ResponseInterface
     */
    public function reorderBlocks(
        RequesterInterface $requester,
        ResponserInterface $responser,
    ): ResponseInterface {
        $blocks = $requester->input()->get('blocks', []);
        
        foreach($blocks as $block) {
            $blockId = $block['id'] ?? null;
            $sortorder = $block['sortorder'] ?? null;
            
            if (!is_numeric($blockId) || !is_numeric($sortorder)) {
                continue;
            }
            
            $this->repository()->updateById(id: (int)$blockId, attributes: [
                'sortorder' => $sortorder,
            ]);
        }
        
        return $responser->json([
            'status' => 200,
        ]);
    }
}