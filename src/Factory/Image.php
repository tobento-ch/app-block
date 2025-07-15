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
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Image
 */
class Image implements BlockFactoryInterface
{
    /**
     * Create a new Image instance.
     *
     * @param PictureGeneratorInterface $pictureGenerator
     * @param ViewInterface $view
     * @param OptionsFactoryInterface $optionsFactory
     * @param null|string $viewNamespace
     * @param bool $generateImagesInBackground
     */
    public function __construct(
        protected PictureGeneratorInterface $pictureGenerator,
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
            name: 'block/image',
            namespace: $this->viewNamespace(),
            options: $options,
        );
        
        return new Block\Image(
            pictureGenerator: $this->pictureGenerator,
            view: $this->view,
            options: $options,
            path: $block['path'] ?? '',
            resource: $block['resource'] ?? '',
            definition: 'block-image',
            imgAlt: $block['imgAlt'] ?? '',
            imgWidth: $block['imgWidth'] ?? 0,
            figcaption: $block['figcaption'] ?? '',
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
            'path' => $entity->localized('data.image.src'),
            'resource' => $entity->get('data.image.storage', ''),
            'imgAlt' => $entity->localized('data.image.alt'),
            'imgWidth' => (int)$entity->get('data.image.width'),
            'figcaption' => $entity->localized('data.image.figcaption'),
            'editable' => $entity->editable(),
        ]);
    }
}