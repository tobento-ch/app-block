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
 * Text field
 *
 * Represents a renderable plain‑text field for a block.
 * The text is HTML‑escaped before output.
 */
class Text implements FieldInterface
{
    /**
     * Create a new Text instance.
     *
     * @param string $text The raw text content (escaped before rendering).
     * @param ViewInterface $view The view service used for escaping.
     */
    public function __construct(
        protected string $text,
        protected ViewInterface $view,
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
        return $this->view->esc($this->text);
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
        return $this->text;
    }
}