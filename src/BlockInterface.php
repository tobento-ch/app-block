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
 * BlockInterface
 */
interface BlockInterface
{
    /**
     * Returns the rendered block content. Must be escaped.
     *
     * @return string
     */
    public function render(): string;
}