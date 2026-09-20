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
use Tobento\App\Crud\Collection\Item;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

/**
 * File field
 *
 * Represents a renderable file field for a block.
 */
class File implements FieldInterface
{
    /**
     * Create a new instance.
     *
     * @param ViewInterface $view The view service used for escaping.
     * @param Item $file
     * @param string|DefinitionInterface|array $definition
     * @param bool $generateImagesInBackground
     */
    public function __construct(
        protected ViewInterface $view,
        protected Item $file,
        protected string|DefinitionInterface|array $definition = 'block-field-file',
        protected bool $generateImagesInBackground = true,
    ) {}

    /**
     * Returns the normalized Item collection for this files field.
     *
     * Use this in custom block views when you need full control over how
     * the files are rendered. The render() method only provides a minimal
     * fallback list; complex blocks (e.g. Downloads) should iterate over
     * files() and build their own markup.
     *
     * @return Item
     */
    public function file(): Item
    {
        return $this->file;
    }
    
    /**
     * Returns the definition.
     *
     * @param null|string $name
     * @return string|DefinitionInterface
     */
    public function definition(null|string $name = null): string|DefinitionInterface
    {
        $definitions = is_array($this->definition)
            ? $this->definition
            : ['default' => $this->definition];
        

        $name = empty($name) ? 'default' : $name;
        
        return $definitions[$name] ?? 'block-field-file';
    }
    
    /**
     * Overrides the picture definition used for generating the image.
     *
     * @param string|DefinitionInterface $definition The picture definition identifier.
     * @return static
     */
    public function withDefinition(string|DefinitionInterface $definition): static
    {
        $this->definition = $definition;
        return $this;
    }

    /**
     * Render the field output.
     *
     * Implementations MUST ensure the returned string is safe for HTML output.
     * This typically means escaping or sanitizing the underlying value
     * depending on the field type (e.g. Text escapes, Html sanitizes).
     *
     * @return string
     * @psalm-suppress UndefinedInterfaceMethod View macros
     */
    public function render(): string
    {
        $file = $this->file();

        $src = $file->get('src', '');
        $storage = $file->raw('storage', 'downloads');
        
        if ($src === '') {
            return '';
        }

        // Fallback name: use 'title' if defined, otherwise filename
        $name = $file->get('title', basename($src));

        $url = $this->view->routeUrl('media.file.download', [
            'storage' => $storage,
            'path' => $src,
        ]);

        return '<a class="block-field-file" href="'.$this->view->esc($url).'">'.
            $this->view->esc($name).
            '</a>';
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
        return $this->file();
    }
}