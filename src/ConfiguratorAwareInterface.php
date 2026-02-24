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

interface ConfiguratorAwareInterface
{
    /**
     * Sets the configurator.
     *
     * @param ConfiguratorInterface $configurator
     * @return static $this
     */
    public function setConfigurator(ConfiguratorInterface $configurator): static;
    
    /**
     * Returns the configurator.
     *
     * @return null|ConfiguratorInterface
     */
    public function configurator(): null|ConfiguratorInterface;
}