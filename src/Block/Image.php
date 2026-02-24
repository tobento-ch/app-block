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
 * Image
 */
class Image implements BlockInterface
{
    /**
     * Create a new Image instance.
     *
     * @param PictureGeneratorInterface $pictureGenerator
     * @param ViewInterface $view
     * @param OptionsInterface $options
     * @param string $path
     * @param string|ResourceInterface $resource
     * @param string|DefinitionInterface $definition
     * @param string $imgAlt
     * @param int $imgWidth
     * @param string $figcaption
     * @param bool $generateImagesInBackground
     * @param null|string $viewName
     */
    public function __construct(
        protected PictureGeneratorInterface $pictureGenerator,
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected string $path = '',
        protected string|ResourceInterface $resource = '',
        protected string|DefinitionInterface $definition = 'block-image',
        protected string $imgAlt = '',
        protected int $imgWidth = 0,
        protected string $figcaption = '',
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
        $pictureTag = $this->pictureGenerator->generate(
            path: $this->path,
            resource: $this->resource,
            definition: $this->definition,
            queue: $this->generateImagesInBackground,
        );
        
        if ($this->imgAlt) {
            $pictureTag->imgAttr('alt', $this->imgAlt);
        }
        
        if ($this->imgWidth >= 50) {
            $width = (int)$pictureTag->img()->attributes()->get('width');
            $height = (int)$pictureTag->img()->attributes()->get('height');
            $pictureTag->imgAttr('width', (string)$this->imgWidth);
            
            if ($width && $height) {
                $newHeight = $this->calculateSize($this->imgWidth, $width, $height);
                $pictureTag->imgAttr('height', (string)$newHeight);
            }
        }
        
        $view = $this->viewName ?: 'block/image';
        
        return $this->view->render(view: $view, data: [
            'block' => $this,
            'pictureTag' => $pictureTag,
            'path' => $this->path,
            'storage' => $this->resource,
            'figcaption' => $this->figcaption,
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
    
    /**
     * Calculates the the given target size.
     *
     * @param int $targetSize
     * @param int $sizeA
     * @param int $sizeB
     * @return int
     */
    protected function calculateSize(int $targetSize, int $sizeA, int $sizeB): int
    {
        $ratio = $sizeB / $sizeA;
        return (int) round((float)$targetSize * (float)$ratio);
    }
}