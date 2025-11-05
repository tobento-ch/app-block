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

use Tobento\Service\Repository\Storage\EntityFactory;
use Tobento\Service\Repository\Storage\HasLocales;
use Tobento\Service\Repository\Storage\LocalesAware;

/**
 * BlockEntityFactory
 */
class BlockEntityFactory extends EntityFactory implements LocalesAware
{
    use HasLocales;
    
    /**
     * Create an entity from array.
     *
     * @param array $attributes
     * @return BlockEntity
     * @throws \Throwable If cannot create block entity
     */
    public function createEntityFromArray(array $attributes): BlockEntity
    {
        // Process the columns reading:
        $attributes = $this->columns->processReading($attributes);

        $block = new BlockEntity($attributes);

        if (empty($block->locale())) {
            $block->setLocale($this->getLocale());
        }
        
        $block->setLocaleFallbacks($this->getLocaleFallbacks());
        
        return $block;
    }
}