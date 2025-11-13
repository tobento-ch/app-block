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
 
namespace Tobento\App\Block\Editor\Block;

use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Editor block.
 */
final class Editor implements BlockInterface
{
    /**
     * Create a new Editor instance.
     *
     * @param BlockInterface $block
     * @param ViewInterface $view
     * @param BlockEntityInterface $entity
     * @param ConfiguratorInterface $configurator
     */
    public function __construct(
        private BlockInterface $block,
        private ViewInterface $view,
        private BlockEntityInterface $entity,
        private ConfiguratorInterface $configurator,
    ) {}
    
    /**
     * Returns the rendered block content. Must be escaped.
     *
     * @return string
     */
    public function render(): string
    {
        return $this->view->render(view: 'block/editor-block', data: [
            'editorBlock' => $this,
            'block' => $this->block,
            'entity' => $this->entity,
            'configurator' => $this->configurator,
        ]);
    }
}