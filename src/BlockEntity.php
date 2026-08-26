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

use Tobento\Service\Collection\Arr;
use Tobento\Service\Collection\Collection;
use Tobento\Service\Repository\Storage\Attribute\StringTranslations;

/**
 * BlockEntity
 */
class BlockEntity implements BlockEntityInterface
{
    /**
     * Create a new BlockEntity instance.
     *
     * @param array $attributes
     */
    public function __construct(
        protected array $attributes = [],
    ) {}

    /**
     * Returns the id.
     *
     * @return int
     */
    public function id(): int
    {
        $value = $this->get('id');
        return is_numeric($value) ? (int)$value : 0;
    }
    
    /**
     * Returns the type.
     *
     * @return string
     */
    public function type(): string
    {
        $value = $this->get('type');
        return is_string($value) ? $value : '';
    }
    
    /**
     * Returns the block status.
     *
     * @return string
     */
    public function status(): string
    {
        $value = $this->get('status');
        return is_string($value) ? $value : '';
    }
    
    /**
     * Returns the editor name.
     *
     * @return string
     */
    public function editor(): string
    {
        $value = $this->get('editor');
        return is_string($value) ? $value : '';
    }

    /**
     * Sets the locale.
     *
     * @param string $locale
     * @return static $this
     */
    public function setLocale(string $locale): static
    {
        $this->attributes['locale'] = $locale;
        return $this;
    }
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string
    {
        $value = $this->get('locale');
        return is_string($value) ? $value : '';
    }
    
    /**
     * Sets the locale fallbacks.
     *
     * @param array<string, string> $fallbacks
     * @return static $this
     */
    public function setLocaleFallbacks(array $fallbacks): static
    {
        $this->attributes['locale_fallbacks'] = $fallbacks;
        return $this;
    }
    
    /**
     * Returns the locale fallbacks.
     *
     * @return array<string, string>
     */
    public function localeFallbacks(): array
    {
        $value = $this->get('locale_fallbacks', []);
        return is_array($value) ? $value : [];
    }
    
    /**
     * Returns the owner value.
     *
     * @return null|string
     */
    public function owner(): null|string
    {
        $owner = $this->get('owner');

        return is_null($owner) || is_string($owner) ? $owner : null;
    }
    
    /**
     * Returns the resource id.
     *
     * @return null|string
     */
    public function resourceId(): null|string
    {
        $id = $this->get('resource_id');
        
        return is_null($id) || is_string($id) ? $id : null;
    }
    
    /**
     * Returns the resource group.
     *
     * @return null|string
     */
    public function resourceGroup(): null|string
    {
        $group = $this->get('resource_group');
        
        return is_null($group) || is_string($group) ? $group : null;
    }
    
    /**
     * Returns the block position.
     *
     * @return null|string
     */
    public function position(): null|string
    {
        $pos = $this->get('position');
        
        return is_null($pos) || is_string($pos) ? $pos : null;
    }
    
    /**
     * Returns the block sortorder.
     *
     * @return int
     */
    public function sortorder(): int
    {
        $value = $this->get('sortorder');
        return is_numeric($value) ? (int)$value : 0;
    }
    
    /**
     * Sets the locale.
     *
     * @param bool $editable
     * @return static $this
     */
    public function setEditable(bool $editable): static
    {
        $this->attributes['editable'] = $editable;
        return $this;
    }
    
    /**
     * Returns whether the block is editable.
     *
     * @return bool
     */
    public function editable(): bool
    {
        $value = $this->get('editable');
        return is_bool($value) ? $value : true;
    }

    /**
     * Returns the block options
     *
     * @return array
     */
    public function options(): array
    {
        $options = $this->get('options');
        
        return is_array($options) ? $options : [];
    }

    /**
     * Returns an attribute value by name.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return Arr::get($this->attributes, $name, $default);
    }
    
    /**
     * Returns a localized attribute value by name.
     *
     * @param string $name
     * @param string $default
     * @param null|string $locale
     * @return string
     */
    public function localized(string $name, string $default = '', null|string $locale = null): string
    {
        $value = $this->get($name);
        $locale = $locale ?: $this->locale();
        
        if (is_string($value)) {
            return $value;
        }
        
        if ($value instanceof StringTranslations) {
            return $value->get(locale: $locale);
        }
        
        if (!is_array($value)) {
            return $default;
        }
        
        if (isset($value[$locale]) && is_string($value[$locale])) {
            return $value[$locale];
        }
        
        $firstKey = (string)array_key_first($value);
        $value = $value[$firstKey] ?? $default;
        return is_string($value) ? $value: '';
    }
    
    /**
     * Object to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return new Collection($this->attributes)->toArray();
    }
}