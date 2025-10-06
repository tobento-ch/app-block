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
 
namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Block;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Exception\BlockCreateException;
use Tobento\App\Crud\Collection\Items;
use Tobento\Service\View\ViewInterface;

/**
 * Downloads
 */
class Downloads implements BlockFactoryInterface
{
    /**
     * Create a new Downloads instance.
     *
     * @param ViewInterface $view
     * @param OptionsFactoryInterface $optionsFactory
     * @param null|string $viewNamespace
     * @param bool $generateImagesInBackground
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsFactoryInterface $optionsFactory,
        protected null|string $viewNamespace = null,
        protected bool $generateImagesInBackground = true,
    ) {}
    
    /**
     * Returns a new instance with the specified view namespace.
     *
     * @param null|string $namespace
     * @return static
     */
    public function withViewNamespace(null|string $namespace): static
    {
        $new = clone $this;
        $new->viewNamespace = $namespace;
        return $new;
    }
    
    /**
     * Returns the view namespace.
     *
     * @return null|string
     */
    public function viewNamespace(): null|string
    {
        return $this->viewNamespace;
    }
    
    /**
     * Create block.
     *
     * @param array<string, mixed> $block
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlock(array $block): BlockInterface
    {
        $options = $this->optionsFactory->createOptions($block['options'] ?? []);
        
        $viewName = Helper::resolveViewName(
            view: $this->view,
            name: 'block/downloads',
            namespace: $this->viewNamespace(),
            options: $options,
        );
        
        $files = $block['files'] ?? [];
        
        if (!is_array($files)) {
            $files = [];
        }
        
        $locale = $block['locale'] ?? 'en';
        
        return new Block\Downloads(
            view: $this->view,
            options: $options,
            files: new Items(
                items: $files,
                locale: $locale,
                localeFallbacks: $block['localeFallbacks'] ?? [],
            ),
            generateImagesInBackground: $this->generateImagesInBackground,
            viewName: $viewName,
        );
    }
    
    /**
     * Create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlockFromEntity(BlockEntityInterface $entity): BlockInterface
    {
        return $this->createBlock(block: [
            'type' => $entity->type(),
            'options' => $entity->options(),
            'files' => $entity->get('data.files', []),
            'editable' => $entity->editable(),
            'locale' => $entity->locale(),
            'localeFallbacks' => $entity->localeFallbacks(),
        ]);
    }
}