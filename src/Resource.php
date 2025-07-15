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
 * Resource
 */
final class Resource implements ResourceInterface
{
    /**
     * Create a new Resource instance.
     *
     * @param string $id
     * @param string $group
     */
    public function __construct(
        private string $id,
        private string $group = '',
    ) {}

    /**
     * Returns a new instance with the specified id.
     *
     * @param string $id
     * @return static
     */
    public function withId(string $id): static
    {
        return new static(id: $id, group: $this->group());
    }
    
    /**
     * Returns the id.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }
    
    /**
     * Returns a new instance with the specified group.
     *
     * @param string $group
     * @return static
     */
    public function withGroup(string $group): static
    {
        return new static(id: $this->id(), group: $group);
    }
    
    /**
     * Returns the group.
     *
     * @return string
     */
    public function group(): string
    {
        return $this->group;
    }
}