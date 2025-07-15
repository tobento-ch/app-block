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
use Tobento\Service\View\ViewInterface;
use Throwable;

/**
 * Text
 */
final class Text implements BlockFactoryInterface
{
    /**
     * Create a new Text instance.
     *
     * @param ViewInterface $view
     * @param OptionsFactoryInterface $optionsFactory
     * @param null|string $viewNamespace
     */
    public function __construct(
        private ViewInterface $view,
        private OptionsFactoryInterface $optionsFactory,
        private null|string $viewNamespace = null,
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
        $viewName = 'block/text-editable';
        
        if (($block['editable'] ?? true) === false) {
            $viewName = 'block/text';
        }
        
        $options = $this->optionsFactory->createOptions($block['options'] ?? []);
        
        $viewName = Helper::resolveViewName(
            view: $this->view,
            name: $viewName,
            namespace: $this->viewNamespace(),
            options: $options,
        );
        
        return new Block\Text(
            view: $this->view,
            options: $options,
            html: $block['html'] ?? '',
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
            'html' => $entity->localized('translation'),
            'options' => $entity->options(),
            'editable' => $entity->editable(),
        ]);
    }
}