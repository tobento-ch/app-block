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
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\View\ViewInterface;

/**
 * Image field
 *
 * Represents a renderable image field for a block.
 *
 * This field uses the PictureGenerator service to generate responsive
 * <picture> markup based on a path, resource, and picture definition.
 *
 */
class Image implements FieldInterface
{
    protected null|Attributes $figureAttributes = null;
    
    protected null|Attributes $figcaptionAttributes = null;

    /**
     * Create a new instance.
     */
    public function __construct(
        protected PictureGeneratorInterface $pictureGenerator,
        protected ViewInterface $view,
        protected string $path = '',
        protected string|ResourceInterface $resource = '',
        protected string|DefinitionInterface $definition = 'block-field-image',
        protected string $imgAlt = '',
        protected int $imgWidth = 0,
        protected string $figcaption = '',
        protected bool $generateImagesInBackground = true,
    ) {}

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
     * Sets or overrides the <figure> tag attributes.
     *
     * @param Attributes|array $attributes Attributes instance or array of attributes.
     * @return static
     */
    public function withFigureAttributes(Attributes|array $attributes): static
    {
        $this->figureAttributes = $attributes instanceof Attributes
            ? $attributes
            : new Attributes($attributes);

        return $this;
    }
    
    /**
     * Sets or overrides the <figcaption> tag attributes.
     *
     * @param Attributes|array $attributes Attributes instance or array of attributes.
     * @return static
     */
    public function withFigcaptionAttributes(Attributes|array $attributes): static
    {
        $this->figcaptionAttributes = $attributes instanceof Attributes
            ? $attributes
            : new Attributes($attributes);

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
        $pictureTag = $this->pictureGenerator->generate(
            path: $this->path,
            resource: $this->resource,
            definition: $this->definition,
            queue: $this->generateImagesInBackground,
        );
        
        if ($this->imgAlt) {
            $pictureTag->imgAttr('alt', $this->imgAlt);
        }
        
        if ($this->imgWidth >= 50) {
            $width = (int)$pictureTag->img()->attributes()->get('width');
            $height = (int)$pictureTag->img()->attributes()->get('height');
            $pictureTag->imgAttr('width', (string)$this->imgWidth);
            
            if ($width && $height) {
                $newHeight = $this->calculateSize($this->imgWidth, $width, $height);
                $pictureTag->imgAttr('height', (string)$newHeight);
            }
        }
        
        // No figure → return picture only
        if ($this->figcaption === '') {
            return (string)$pictureTag;
        }

        // Build figure + figcaption
        $figureAttr = $this->figureAttributes ? (string)$this->figureAttributes : '';
        $figcaptionAttr = $this->figcaptionAttributes ? (string)$this->figcaptionAttributes : '';

        return sprintf(
            '<figure%s>%s<figcaption%s>%s</figcaption></figure>',
            $figureAttr,
            (string)$pictureTag,
            $figcaptionAttr,
            $this->view->esc($this->figcaption)
        );
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
     * Calculates the the given target size.
     *
     * @param int $targetSize
     * @param int $sizeA
     * @param int $sizeB
     * @return int
     */
    protected function calculateSize(int $targetSize, int $sizeA, int $sizeB): int
    {
        $ratio = $sizeB / $sizeA;
        return (int) round((float)$targetSize * (float)$ratio);
    }
    
    /**
     * Returns the raw underlying value.
     *
     * @return mixed
     */
    public function value(): mixed
    {
        return [
            'path' => $this->path,
            'resource' => $this->resource,
            'definition' => $this->definition,
            'imgAlt' => $this->imgAlt,
            'imgWidth' => $this->imgWidth,
            'figcaption' => $this->figcaption,
            'generateImagesInBackground' => $this->generateImagesInBackground,
            'figureAttributes' => $this->figureAttributes ?? null,
            'figcaptionAttributes' => $this->figcaptionAttributes ?? null,
        ];
    }
}