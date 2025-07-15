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
 * ResourceResolverInterface
 */
interface ResourceResolverInterface
{
    /**
     * Returns the resolved resource or null.
     *
     * @return null|ResourceInterface
     */
    public function resolve(): null|ResourceInterface;
}