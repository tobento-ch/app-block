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

namespace Tobento\App\Block\Exception;

/**
 * EditorNotFoundException
 */
class EditorNotFoundException extends BlockException
{
    /**
     * Create a new EditorNotFoundException.
     *
     * @param string $editor
     */
    public function __construct(
        protected string $editor,
    ) {
        parent::__construct(sprintf('Editor %s not found', $editor));
    }
    
    /**
     * Returns the editor.
     *
     * @return string
     */
    public function editor(): string
    {
        return $this->editor;
    }
}