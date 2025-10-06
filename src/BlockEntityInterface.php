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
 * BlockEntityInterface
 */
interface BlockEntityInterface
{
    /**
     * Returns the id.
     *
     * @return int
     */
    public function id(): int;
    
    /**
     * Returns the type.
     *
     * @return string
     */
    public function type(): string;
    
    /**
     * Returns the block status.
     *
     * @return string
     */
    public function status(): string;
    
    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return static $this
     */
    public function setLocale(string $locale): static;
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string;
    
    /**
     * Sets the locale fallbacks.
     *
     * @param array<string, string> $fallbacks
     * @return static $this
     */
    public function setLocaleFallbacks(array $fallbacks): static;
    
    /**
     * Returns the locale fallbacks.
     *
     * @return array<string, string>
     */
    public function localeFallbacks(): array;
    
    /**
     * Returns the resource id.
     *
     * @return null|string
     */
    public function resourceId(): null|string;
    
    /**
     * Returns the resource group.
     *
     * @return null|string
     */
    public function resourceGroup(): null|string;
    
    /**
     * Returns the block position.
     *
     * @return null|string
     */
    public function position(): null|string;
    
    /**
     * Returns the block sortorder.
     *
     * @return int
     */
    public function sortorder(): int;
    
    /**
     * Sets the locale.
     *
     * @param bool $editable
     * @return static $this
     */
    public function setEditable(bool $editable): static;
    
    /**
     * Returns whether the block is editable.
     *
     * @return bool
     */
    public function editable(): bool;
    
    /**
     * Returns the block options
     *
     * @return array
     */
    public function options(): array;
    
    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed;
    
    /**
     * Returns a localized attribute value by name.
     *
     * @param string $name
     * @param string $default
     * @param null|string $locale
     * @return string
     */
    public function localized(string $name, string $default = '', null|string $locale = null): string;
    
    /**
     * Object to array.
     *
     * @return array
     */
    public function toArray(): array;
}