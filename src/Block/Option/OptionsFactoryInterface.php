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
 
namespace Tobento\App\Block\Block\Option;

/**
 * OptionsFactoryInterface
 */
interface OptionsFactoryInterface
{
    /**
     * Create a new options instance.
     *
     * @param array $options
     * @return OptionsInterface
     */
    public function createOptions(array $options): OptionsInterface;
}