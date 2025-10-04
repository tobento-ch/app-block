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

use IteratorAggregate;

/**
 * @extends IteratorAggregate<string, EditableBlockInterface>
 */
interface EditableBlocksInterface extends IteratorAggregate
{
    /**
     * Add a block.
     *
     * @param string $name An unique name.
     * @param string|array|EditableBlockInterface $block
     * @return static $this
     */
    public function add(string $name, string|array|EditableBlockInterface $block): static;
    
    /**
     * Returns true if block exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns a block by name.
     *
     * @param string $name
     * @return null|EditableBlockInterface
     */
    public function get(string $name): null|EditableBlockInterface;
    
    /**
     * Returns a new instance with the blocks sorted.
     *
     * @param null|callable $callback If null, it sorts by title.
     * @return static
     */
    public function sort(null|callable $callback = null): static;
    
    /**
     * Returns all block names.
     *
     * @return array<array-key, string>
     */
    public function names(): array;
    
    /**
     * Returns all blocks.
     *
     * @return array<string, EditableBlockInterface>
     */
    public function all(): array;
}