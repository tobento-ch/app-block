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
 
namespace Tobento\App\Block\Block;

use Tobento\App\Block\Block\Option\OptionsInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\View\ViewInterface;

/**
 * ImageGallery block.
 */
class ImageGallery implements BlockInterface
{
    /**
     * Create a new ImageGallery instance.
     *
     * @param PictureGeneratorInterface $pictureGenerator
     * @param ViewInterface $view
     * @param OptionsInterface $options
     * @param array<array-key, array<string, mixed>> $images
     * @param string|DefinitionInterface $definitionThumbnail
     * @param string|DefinitionInterface $definition
     * @param bool $generateImagesInBackground
     * @param null|string $viewName
     */
    public function __construct(
        protected PictureGeneratorInterface $pictureGenerator,
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected array $images = [],
        protected string|DefinitionInterface $definitionThumbnail = 'block-image-gallery',
        protected string|DefinitionInterface $definition = 'block-image-gallery-large',
        protected bool $generateImagesInBackground = true,
        protected null|string $viewName = null,
    ) {}
    
    /**
     * Returns the rendered block content. Must be escaped.
     *
     * @return string
     */
    public function render(): string
    {
        $view = $this->viewName ?: 'block/image-gallery';
        
        return $this->view->render(view: $view, data: [
            'block' => $this,
            'images' => $this->images,
            'pictureDefinitionThumbnail' => $this->definitionThumbnail,
            'pictureDefinition' => $this->definition,
            'generateImagesInBackground' => $this->generateImagesInBackground,
        ]);
    }
    
    /**
     * Returns the options.
     *
     * @return OptionsInterface
     */
    public function options(): OptionsInterface
    {
        return $this->options;
    }
}