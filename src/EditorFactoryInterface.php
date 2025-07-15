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
 
namespace Tobento\App\Block;

/**
 * EditorFactoryInterface
 */
interface EditorFactoryInterface
{
    /**
     * Returns the created editor.
     *
     * @param string $name
     * @return EditorInterface
     */
    public function createEditor(string $name): EditorInterface;
}