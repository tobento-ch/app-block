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
use Tobento\Service\View\ViewInterface;

/**
 * Text
 */
class Text implements BlockInterface
{
    /**
     * Create a new Text instance.
     *
     * @param ViewInterface $view
     * @param OptionsInterface $options
     * @param string $html
     * @param null|string $viewName
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected string $html,
        protected null|string $viewName = null,
    ) {}
    
    /**
     * Returns the rendered block content. Must be escaped.
     *
     * @return string
     */
    public function render(): string
    {
        $view = $this->viewName ?: 'block/text';
        
        return $this->view->render(view: $view, data: ['block' => $this]);
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