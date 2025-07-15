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

use Tobento\App\Block\Exception\EditorNotFoundException;

/**
 * EditorsInterface
 */
interface EditorsInterface
{
    /**
     * Returns true if editor exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns an editor by name.
     *
     * @param string $name
     * @return EditorInterface
     * @throws EditorNotFoundException
     */
    public function get(string $name): EditorInterface;
    
    /**
     * Returns all editor names.
     *
     * @return array<array-key, string>
     */
    public function names(): array;
}