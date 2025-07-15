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
 * ResourceInterface
 */
interface ResourceInterface
{
    /**
     * Returns a new instance with the specified id.
     *
     * @param string $id
     * @return static
     */
    public function withId(string $id): static;
    
    /**
     * Returns the id.
     *
     * @return string
     */
    public function id(): string;
    
    /**
     * Returns a new instance with the specified group.
     *
     * @param string $group
     * @return static
     */
    public function withGroup(string $group): static;
    
    /**
     * Returns the group.
     *
     * @return string
     */
    public function group(): string;
}