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

/**
 * ListField
 *
 * Represents a renderable list field for a block.
 */
class ListField implements FieldInterface
{
    /**
     * Create a new instance.
     *
     * @param ViewInterface $view The view service used for escaping.
     * @param array $items
     */
    public function __construct(
        protected ViewInterface $view,
        protected array $items,
    ) {}

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
        if (empty($this->items)) {
            return '';
        }

        $html = '<ul class="block-field-list">';

        foreach ($this->items as $item) {
            $value = $this->ensureString($item);
            $html .= '<li>'.$this->view->esc($value).'</li>';
        }

        $html .= '</ul>';

        return $html;
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
     * Normalizes a list item into a string or Stringable value.
     *
     * Strings and Stringable objects are returned unchanged so Htmlable
     * values can still be handled by the view esc() method. Scalars are
     * cast to string, null becomes an empty string, and arrays/objects
     * fall back to JSON encoding.
     *
     * Escaping is not done here; render() handles that.
     *
     * @param mixed $item The raw list item value.
     * @return string|\Stringable The normalized value ready for rendering.
     */
    public function ensureString(mixed $item): string|\Stringable
    {
        if (is_string($item) || $item instanceof \Stringable) {
            return $item;
        }

        if (is_numeric($item) || is_bool($item)) {
            return (string)$item;
        }

        if ($item === null) {
            return '';
        }

        // Fallback: JSON encode arrays/objects safely
        return json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    /**
     * Returns the raw underlying value.
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->items;
    }
}