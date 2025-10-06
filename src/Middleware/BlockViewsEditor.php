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

namespace Tobento\App\Block\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Resource;
use Tobento\App\Block\ResourceInterface;
use Tobento\App\Block\ResourceResolverInterface;
use Tobento\Service\Repository\Storage\LocalesAware;
use Tobento\Service\View\ViewInterface;

/**
 * BlockViewsEditor
 */
class BlockViewsEditor implements MiddlewareInterface
{
    /**
     * Create a new BlockViewEditor instance.
     *
     * @param EditorsInterface $editors
     * @param string $editorName
     * @param ResourceResolverInterface $resourceResolver
     * @param ViewInterface $view
     * @param bool $editable
     * @param null|string $resourceId
     * @param null|string $resourceGroup
     */
    public function __construct(
        protected EditorsInterface $editors,
        protected string $editorName,
        protected ResourceResolverInterface $resourceResolver,
        protected ViewInterface $view,
        protected bool $editable = true,
        protected null|string $resourceId = null,
        protected null|string $resourceGroup = null,
    ) {}
    
    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $resource = $this->resourceResolver->resolve();
        
        if (is_null($resource)) {
            return $handler->handle($request);
        }

        if ($this->resourceId) {
            $resource = $resource->withId($this->resourceId);
        }
        
        if ($this->resourceGroup) {
            $resource = $resource->withGroup($this->resourceGroup);
        }
        
        if ($this->editable) {
            $this->createEditableViewBlocks($resource);
        } else {
            $this->createViewBlocks($resource);
        }
        
        $request = $request->withAttribute(ResourceInterface::class, $resource);
        
        return $handler->handle($request);
    }
    
    /**
     * Create editable view blocks.
     *
     * @param ResourceInterface $resource
     * @return void
     */
    public function createEditableViewBlocks(ResourceInterface $resource): void
    {
        $editor = $this->editors->get($this->editorName);
        $blocks = $editor->getBlockRepository()->findAllByResource($resource);
        
        // Blocks by position:
        $blocksByPosition = [];
        
        foreach($blocks as $entity) {
            $blocksByPosition[$entity->position()][] = $entity;
        }
        
        // Add views for the collected positions and its blocks:
        $this->view->on(
            '*', 
            static function(array $data, ViewInterface $view, string $key) use ($blocksByPosition, $editor, $resource): array {
                if (!str_starts_with($key, 'blocks.')) {
                    return $data;
                }

                $position = substr($key, 7);
                $view->add(key: $key, view: 'block/views-blocks-editor');
                $data['editor'] = $editor;
                $data['blocks'] = $blocksByPosition[$position] ?? [];
                $data['resourceId'] = $resource->id();
                $data['resourceGroup'] = $resource->group();
                $data['position'] = $position;
                return $data;
            }
        );        
    }
    
    /**
     * Create view blocks.
     *
     * @param ResourceInterface $resource
     * @return void
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function createViewBlocks(ResourceInterface $resource): void
    {
        $editor = $this->editors->get($this->editorName);
        $blocks = $editor->getBlockRepository()->findAllByResource($resource);
        
        $blocksByPosition = [];
        $locale = $editor->locale();
        
        if ($editor->getBlockRepository() instanceof LocalesAware) {
            $locale = $editor->getBlockRepository()->getLocale();
        }
        
        foreach($blocks as $entity) {
            $entity->setLocale($locale);
            $entity->setLocaleFallbacks($editor->localeFallbacks());
            $entity->setEditable(false);
            $blocksByPosition[$entity->position()][] = $editor->getBlockFactory()->createBlockFromEntity($entity);
        }
        
        // Add views for the collected positions and its blocks:
        $this->view->on('*', static function(array $data, ViewInterface $view, string $key) use ($blocksByPosition): array {
            if (!str_starts_with($key, 'blocks.')) {
                return $data;
            }

            $position = substr($key, 7);
            $view->add(key: $key, view: 'block/blocks');
            $data['blocks'] = $blocksByPosition[$position] ?? [];
            $data['position'] = $position;
            return $data;
        });
    }
}