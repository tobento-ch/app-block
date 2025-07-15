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

use Tobento\Service\Tag\AttributesInterface;

/**
 * OptionsInterface
 */
interface OptionsInterface
{
    /**
     * To tag attributes.
     *
     * @return AttributesInterface
     */
    public function toTagAttributes(): AttributesInterface;

    /**
     * Returns an option by name.
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name): mixed;
    
    /**
     * Returns all options.
     *
     * @return array<string, mixed>
     */
    public function all(): array;
}