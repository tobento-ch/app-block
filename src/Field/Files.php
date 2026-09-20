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
use Tobento\App\Crud\Collection\Items;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

/**
 * Files field
 *
 * Represents a renderable files field for a block.
 *
 */
class Files implements FieldInterface
{
    /**
     * Create a new instance.
     *
     * @param ViewInterface $view The view service used for escaping.
     * @param Items $files
     * @param string|DefinitionInterface|array $definition
     * @param bool $generateImagesInBackground
     */
    public function __construct(
        protected ViewInterface $view,
        protected Items $files,
        protected string|DefinitionInterface|array $definition = 'block-field-files',
        protected bool $generateImagesInBackground = true,
    ) {}

    /**
     * Returns the normalized Items collection for this files field.
     *
     * Use this in custom block views when you need full control over how
     * the files are rendered. The render() method only provides a minimal
     * fallback list; complex blocks (e.g. Downloads) should iterate over
     * files() and build their own markup.
     *
     * @return Items
     */
    public function files(): Items
    {
        return $this->files;
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
        
        return $definitions[$name] ?? 'block-field-files';
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
        $html = '<ul class="block-field-files">';

        foreach ($this->files() as $file) {

            $src = $file->get('src', '');
            $storage = $file->raw('storage', 'downloads');

            if ($src === '') {
                continue;
            }

            // Fallback name: use 'name' if defined, otherwise use the filename
            $name = $file->get('name', basename($src));

            $url = $this->view->routeUrl('media.file.download', ['storage' => $storage, 'path' => $src]);

            $html .= '<li class="block-field-files-item">';
            $html .= '<a href="'.$this->view->esc($url).'">'. $this->view->esc($name).'</a>';
            $html .= '</li>';
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
     * Returns the raw underlying value.
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return $this->files();
    }
}