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

use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Block;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Exception\BlockCreateException;
use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;
use Tobento\App\Crud\Collection\Item;
use Tobento\App\Crud\Collection\Items;
use Tobento\Service\Collection\Arr;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\app;

/**
 * Base factory for editable field‑composed blocks.
 *
 * This abstraction exists to centralize the hydration and field
 * mapping logic required by field‑based blocks. Each concrete
 * factory defines only its CRUD/editor field‑type mapping via
 * configureFieldMapping(), avoiding duplicated hydration logic
 * across factories such as Hero, Banner, Image and Feature.
 *
 * The factory transforms raw block data or block entities into
 * typed field objects and produces a renderable Fields block
 * instance. Rendering is handled by Block\Fields and the individual
 * FieldInterface implementations.
 */
abstract class AbstractFields implements BlockFactoryInterface
{
    /**
     * Create a new Items factory instance.
     *
     * @param ViewInterface $view
     * @param OptionsFactoryInterface $optionsFactory
     * @param null|string $viewNamespace
     * @param bool $generateImagesInBackground
     */
    public function __construct(
        protected ViewInterface $view,
        protected OptionsFactoryInterface $optionsFactory,
        protected null|string $viewNamespace = null,
        protected bool $generateImagesInBackground = true,
    ) {}
    
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
    abstract public function type(): string;

    /**
     * Returns the mapping from CRUD/editor field types to
     * renderable block field classes.
     *
     * Example:
     * [
     *     'text'  => Field\Text::class,
     *     'html'  => Field\Html::class,
     *     'image' => Field\Image::class,
     * ]
     *
     * @return iterable<string, class-string<FieldInterface>>
     */
    abstract protected function configureFieldMapping(): iterable;
    
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
    abstract protected function configureEntityFieldMapping(): array;

    
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
    abstract protected function configureTranslatableFields(): array;
    
    /**
     * Returns the picture definitions for image fields.
     *
     * The array keys are field names (dot‑notation), and the values are the
     * corresponding picture definition identifiers used by the PictureGenerator.
     *
     * Example:
     * [
     *     'data.image' => 'block-hero',
     *     'data.image-gallery' => ['large' => 'block-image-gallery', 'thumbnail' => 'block-image-gallery-thumbnail'],
     * ]
     *
     * @return array<string, string|array<string, string>> Field name to picture definition
     */
    protected function configureImageDefinitions(): array
    {
        return [];
    }
    
    /**
     * Returns toolbar configurations for HtmlTextEditor fields.
     * Blocks without text editor fields simply return an empty array.
     *
     * Keys are field names (dot‑notation), values are arrays defining
     * the enabled toolbar tools for that field.
     *
     * Example:
     * [
     *     'translation' => ['bold', 'italic', 'link'],
     *     'data.description' => ['bold', 'list'],
     * ]
     *
     * @return array<string,array> Field name → toolbar configuration
     */
    protected function configureTextEditorToolbars(): array
    {
        return [];
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
        return 'block/fields';
    }
    
    /**
     * Returns a new instance with the specified view namespace.
     *
     * @param null|string $namespace
     * @return static
     */
    public function withViewNamespace(null|string $namespace): static
    {
        $new = clone $this;
        $new->viewNamespace = $namespace;
        return $new;
    }

    /**
     * Returns the view namespace.
     *
     * @return null|string
     */
    public function viewNamespace(): null|string
    {
        return $this->viewNamespace;
    }

    /**
     * Create block.
     *
     * @param array<string, mixed> $block
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlock(array $block): BlockInterface
    {
        $options = $this->optionsFactory->createOptions($block['options'] ?? []);
        
        $viewName = Helper::resolveViewName(
            view: $this->view,
            name: $this->viewName(),
            namespace: $this->viewNamespace(),
            options: $options,
        );

        $editable = $block['editable'] ?? true;
        $editable = is_bool($editable) ? $editable : true;
        
        return new Block\Fields(
            view: $this->view,
            options: $options,
            editable: $editable,
            fields: $this->hydrateFields($block),
            viewName: $viewName,
            generateImagesInBackground: $this->generateImagesInBackground,
        );
    }

    /**
     * Create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlockFromEntity(BlockEntityInterface $entity): BlockInterface
    {
        $translatable = $this->configureTranslatableFields();
        
        $block = [
            'type' => $entity->type(),
            'options' => $entity->options(),
            'editable' => $entity->editable(),
            'data' => $entity->get('data', []),
            'content' => $entity->get('content', ''),
            'translation' => $entity->get('translation'),
            'translations' => $entity->get('translations', []),
        ];
        
        foreach ($translatable as $fieldName) {
            $block = Arr::set($block, $fieldName, $entity->localized($fieldName));
        }

        return $this->createBlock($block);
    }
    
    /**
     * Hydrates raw CRUD/editor field data into typed Field objects
     * using the mapping defined in configureFieldMapping().
     *
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return array<string, FieldInterface> Hydrated fields indexed by field name.
     */
    protected function hydrateFields(array $block): array
    {
        $mapping  = $this->configureFieldMapping();
        $hydrated = [];
        
        foreach ($mapping as $fieldName => $class) {

            // Resolve nested raw data using dot-notation
            $raw = Arr::get($block, $fieldName);

            // Skip if nothing found
            if ($raw === null) {
                continue;
            }

            $field = $this->createField($class, $fieldName, $raw, $block);

            if ($field !== null) {
                $hydrated[$fieldName] = $field;
            }
        }

        return $hydrated;
    }

    /**
     * Dispatches the mapped block Field class to its dedicated
     * creation method. Unknown classes are silently skipped.
     *
     * @param class-string<FieldInterface> $class
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return null|FieldInterface
     */
    protected function createField(string $class, string $name, mixed $raw, array $block): null|FieldInterface
    {
        return match ($class) {
            Field\Data::class => $this->createDataField($name, $raw, $block),
            Field\File::class => $this->createFileField($name, $raw, $block),
            Field\Files::class => $this->createFilesField($name, $raw, $block),
            Field\Html::class => $this->createHtmlField($name, $raw, $block),
            Field\HtmlTextEditor::class => $this->createHtmlTextEditorField($name, $raw, $block),
            Field\Image::class => $this->createImageField($name, $raw, $block),
            Field\ListField::class => $this->createListField($name, $raw, $block),
            Field\Text::class => $this->createTextField($name, $raw, $block),
            default => null,
        };
    }
    
    /**
     * Creates a Data field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createDataField(string $name, mixed $raw, array $block): FieldInterface
    {
        return new Field\Data(
            data: is_array($raw) ? $raw : [],
        );
    }

    /**
     * Creates a File field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createFileField(string $name, mixed $raw, array $block): FieldInterface
    {
        $file = is_array($raw) ? $raw : [];
        $locale = $block['locale'] ?? 'en';
        
        return new Field\File(
            view: $this->view,
            file: new Item(
                attributes: $file,
                locale: $locale,
                localeFallbacks: $block['localeFallbacks'] ?? [],
            ),
            definition: $this->configureImageDefinitions()[$name] ?? 'block-field-file',
            generateImagesInBackground: $this->generateImagesInBackground,
        );
    }
    
    /**
     * Creates a Files field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createFilesField(string $name, mixed $raw, array $block): FieldInterface
    {
        $files = is_array($raw) ? $raw : [];
        $locale = $block['locale'] ?? 'en';
        
        return new Field\Files(
            view: $this->view,
            files: new Items(
                items: $files,
                locale: $locale,
                localeFallbacks: $block['localeFallbacks'] ?? [],
            ),
            definition: $this->configureImageDefinitions()[$name] ?? 'block-field-files',
            generateImagesInBackground: $this->generateImagesInBackground,
        );
    }

    /**
     * Creates a Html field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createHtmlField(string $name, mixed $raw, array $block): FieldInterface
    {
        return new Field\Html(
            html: is_string($raw) ? $raw : '',
            view: $this->view,
        );
    }
    
    /**
     * Creates a HtmlTextEditor field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createHtmlTextEditorField(string $name, mixed $raw, array $block): FieldInterface
    {
        $toolbar = $this->configureTextEditorToolbars()[$name] ?? [];
        
        return new Field\HtmlTextEditor(
            html: is_string($raw) ? $raw : '',
            field: $name,
            translatable: in_array($name, $this->configureTranslatableFields()),
            itemIndex: null,
            toolbar: $toolbar,
            view: $this->view,
        );
    }

    /**
     * Creates an Image field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createImageField(string $name, mixed $raw, array $block): FieldInterface
    {
        if (!is_array($raw)) {
            return new Field\Image(
                pictureGenerator: $this->getService(PictureGeneratorInterface::class),
                view: $this->view,
                path: '',
                resource: '',
                definition: $this->configureImageDefinitions()[$name] ?? 'block-field-image',
                imgAlt: '',
                imgWidth: 100,
                figcaption: '',
                generateImagesInBackground: $this->generateImagesInBackground,
            );
        }
        
        return new Field\Image(
            pictureGenerator: $this->getService(PictureGeneratorInterface::class),
            view: $this->view,
            path: $raw['src'] ?? '',
            resource: $raw['storage'] ?? '',
            definition: $this->configureImageDefinitions()[$name] ?? 'block-field-image',
            imgAlt: $raw['alt'] ?? '',
            imgWidth: (int)($raw['width'] ?? 300),
            figcaption: $raw['figcaption'] ?? '',
            generateImagesInBackground: $this->generateImagesInBackground,
        );
    }
    
    /**
     * Creates a List field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createListField(string $name, mixed $raw, array $block): FieldInterface
    {
        // Raw list data must be an array; otherwise treat as empty list
        $items = is_array($raw) ? $raw : [];

        return new Field\ListField(
            view: $this->view,
            items: $items,
        );
    }
    
    /**
     * Creates a Text field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @param array<string, mixed> $block Raw block data from the Editable layer.
     * @return FieldInterface
     */
    protected function createTextField(string $name, mixed $raw, array $block): FieldInterface
    {
        return new Field\Text(
            text: is_string($raw) ? $raw : '',
            view: $this->view,
        );
    }
    
    /**
     * Get a service from the container.
     *
     * @param string $name
     * @return mixed
     */
    protected function getService(string $name): mixed
    {
        return app()->get($name);
    }
}