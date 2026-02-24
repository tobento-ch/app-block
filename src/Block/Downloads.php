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
use Tobento\App\Crud\Collection\Items;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Downloads
 */
class Downloads implements BlockInterface
{
    /**
     * Create a new Downloads instance.
     *
     * @param ViewInterface $view
     * @param OptionsInterface $options
     * @param Items $files
     * @param string|DefinitionInterface $definition
     * @param bool $generateImagesInBackground
     * @param null|string $viewName
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected Items $files,
        protected string|DefinitionInterface $definition = 'block-downloads',
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
        $view = $this->viewName ?: 'block/downloads';

        return $this->view->render(view: $view, data: [
            'block' => $this,
            'files' => $this->files,
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