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

use Tobento\Service\Repository\RepositoryInterface;

/**
 * BlockRepositoryInterface
 */
interface BlockRepositoryInterface extends RepositoryInterface
{
    /**
     * Create entity from array.
     *
     * @param array $entity
     * @return BlockEntityInterface
     */
    public function createEntity(array $entity): BlockEntityInterface;
    
    /**
     * Returns the found entities using by the given resource.
     *
     * @param ResourceInterface $resource
     * @return iterable<object>
     */
    public function findAllByResource(ResourceInterface $resource): iterable;
}