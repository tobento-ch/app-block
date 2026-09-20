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

namespace Tobento\App\Block\Field;

use Tobento\App\Block\FieldInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Collection\Arr;

/**
 * Data field
 *
 * Represents a structured data container for a block.
 * Used for arbitrary arrays, option lists, and key-value data.
 * Not intended for direct rendering.
 */
class Data implements FieldInterface
{
    /**
     * Create a new Data instance.
     *
     * @param array $data The raw data.
     */
    public function __construct(
        protected array $data = [],
    ) {}

    /**
     * Checks whether the given key exists in the data array.
     * Supports dot notation for nested keys (e.g. "layout.image").
     *
     * @param string $key The key to check.
     * @return bool True if the key exists, otherwise false.
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }
    
    /**
     * Checks whether the given value exists in the data array.
     *
     * @param mixed $value The value to check.
     * @return bool True if the value exists, otherwise false.
     */
    public function contains(mixed $value): bool
    {
        return in_array($value, $this->data, true);
    }

    /**
     * Returns the value for the given key from the data array.
     *
     * Supports dot notation for nested keys (e.g. "layout.image").
     * If the key does not exist, the provided default value is returned.
     *
     * @param string $key The key to retrieve.
     * @param mixed  $default The value to return if the key is not present.
     * @return mixed The value associated with the key, or the default value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->data, $key, $default);
    }
    
    /**
     * Render the field output.
     *
     * Implementations MUST ensure the returned string is safe for HTML output.
     * This typically means escaping or sanitizing the underlying value
     * depending on the field type (e.g. Text escapes, Html sanitizes).
     *
     * @return string
     */
    public function render(): string
    {
        // Data is not rendered directly
        return '';
    }
    
    /**
     * Render the field output for the editor (CRUD layer).
     *
     * Implementations MUST ensure the returned HTML is safe for injection
     * into the editor UI. This typically means escaping or sanitizing the
     * underlying value depending on the field type (e.g. Text escapes,
     * HtmlField sanitizes before placing content inside form inputs or
     * JS‑enhanced editors).
     *
     * @return string
     */
    public function renderEditable(): string
    {
        return $this->render();
    }
    
    /**
     * Returns the raw underlying value.
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->data;
    }
}