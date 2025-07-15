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
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Hero
 */
class Hero implements BlockInterface
{
    /**
     * Create a new Hero instance.
     *
     * @param PictureGeneratorInterface $pictureGenerator
     * @param ViewInterface $view
     * @param OptionsInterface $options
     * @param string $html
     * @param string $path
     * @param string|ResourceInterface $resource
     * @param string|DefinitionInterface $definition
     * @param string $imgAlt
     * @param bool $generateImagesInBackground
     * @param null|string $viewName
     */
    public function __construct(
        protected PictureGeneratorInterface $pictureGenerator,
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected string $html = '',
        protected string $path = '',
        protected string|ResourceInterface $resource = '',
        protected string|DefinitionInterface $definition = 'block-hero',
        protected string $imgAlt = '',
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
        
        $view = $this->viewName ?: 'block/hero';
        
        return $this->view->render(view: $view, data: [
            'block' => $this,
            'pictureTag' => $pictureTag,
            'path' => $this->path,
            'storage' => $this->resource,
        ]);
    }
    
    /**
     * Returns the html.
     *
     * @return string
     */
    public function html(): string
    {
        return $this->html;
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