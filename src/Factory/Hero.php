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
 * Hero Block Factory
 */
class Hero extends AbstractFields
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
        return 'hero';
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
            'data.image' => Field\Image::class,
            'translation' => Field\HtmlTextEditor::class,
        ];
    }
    
    /**
     * Returns the mapping from block field names to entity storage keys.
     *
     * Example:
     * [
     *     'html'  => 'translation',
     *     'image' => 'data.image',
     * ]
     *
     * @return array<string, string>
     */
    protected function configureEntityFieldMapping(): array
    {
        return [
            'data.image' => 'data.image',
            'translation' => 'translation',
        ];
    }
    
    /**
     * Returns the field names that are translatable.
     *
     * @return array<int, string>
     */
    protected function configureTranslatableFields(): array
    {
        return ['data.image.src', 'data.image.alt', 'data.image.figcaption', 'translation'];
    }
    
    /**
     * Returns picture definitions for image fields.
     * Blocks without image fields simply return an empty array.
     *
     * @return array<string,string>
     */
    protected function configureImageDefinitions(): array
    {
        return ['data.image' => 'block-hero'];
    }
    
    /**
     * Returns the base view name for fields blocks.
     *
     * The returned name is used as the base identifier for template
     * resolution. The final view name may be overridden by namespaces
     * or block options through Helper::resolveViewName().
     *
     * @return string
     */
    public function viewName(): string
    {
        return 'block/hero';
    }
}