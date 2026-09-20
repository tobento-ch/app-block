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

namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;

/**
 * FAQ Block Factory
 */
class Faq extends AbstractItems
{
    /**
     * Returns the block type handled by this factory.
     *
     * This value must match the type returned by the corresponding
     * editable block (Editable\AbstractItems::type()) so the block
     * manager can correctly pair editable configuration, hydration,
     * and rendering.
     *
     * @return string
     */
    public function type(): string
    {
        return 'faq';
    }
    
    /**
     * Returns the mapping from CRUD/editor field types to
     * renderable block field classes.
     *
     * @return iterable<string, class-string<FieldInterface>>
     */
    protected function configureFieldMapping(): iterable
    {
        return [
            'question' => Field\Text::class,
            'answer' => Field\HtmlTextEditor::class,
            'open' => Field\Text::class,
        ];
    }
    
    /**
     * Returns the field names that are translatable.
     *
     * Example:
     * [
     *     'question',
     *     'answer',
     * ]
     *
     * @return array<int, string>
     */
    protected function configureTranslatableFields(): array
    {
        return ['question', 'answer'];
    }
    
    /**
     * Returns the base view name for item‑list blocks.
     *
     * The returned name is used as the base identifier for template
     * resolution. The final view name may be overridden by namespaces
     * or block options through Helper::resolveViewName().
     *
     * @return string
     */
    public function viewName(): string
    {
        return 'block/faq';
    }
}