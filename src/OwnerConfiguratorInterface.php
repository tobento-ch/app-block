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

use Tobento\App\Block\BlockEntityInterface;

/**
 * Determines whether a configurator owns a given block entity.
 */
interface OwnerConfiguratorInterface
{
    /**
     * Returns true if the configurator owns the block entity.
     *
     * @param BlockEntityInterface $entity
     * @return bool
     */
    public function owns(BlockEntityInterface $entity): bool;
}