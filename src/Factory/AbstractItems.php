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
use Tobento\Service\Iterable\Iter;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\Repository\Storage\Attribute\StringTranslations;
use Tobento\Service\View\ViewInterface;
use function Tobento\App\app;

/**
 * Base factory for editable item‑list blocks.
 *
 * This abstraction exists to centralize the hydration and field
 * mapping logic required by item‑based blocks. Each concrete
 * factory defines only its CRUD/editor field‑type mapping via
 * configureFieldMapping(), avoiding duplicated hydration logic
 * across factories such as Faq, Features and TeamMembers.
 *
 * The factory transforms raw block data or block entities into
 * typed field objects and produces a renderable Items block
 * instance. Rendering is handled by Block\Items and the individual
 * FieldInterface implementations.
 */
abstract class AbstractItems implements BlockFactoryInterface
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
     *     'image' => 'block-hero',
     *     'image-gallery' => ['large' => 'block-image-gallery', 'thumbnail' => 'block-image-gallery-thumbnail'],
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
     *     'answer' => ['bold', 'italic', 'link'],
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
        return 'block/items';
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
            
        return new Block\Items(
            view: $this->view,
            options: $options,
            editable: $editable,
            items: $this->hydrateItems($block['items'] ?? []),
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
        $items = $entity->get('data.items');
        $items = is_array($items) ? $items : [];
        
        return $this->createBlock(block: [
            'type' => $entity->type(),
            'items' => $this->localizeItems($items, $entity),
            'options' => $entity->options(),
            'editable' => $entity->editable(),
        ]);
    }
    
    /**
     * Hydrates raw CRUD/editor item data into typed Field objects
     * using the mapping defined in configureFieldMapping().
     *
     * @param array<int, array<string, mixed>> $items Raw item data from the Editable layer.
     * @return array<int, array<string, FieldInterface>> Hydrated items where each value is a field instance.
     */
    protected function hydrateItems(array $items): array
    {
        $mapping = Iter::toArray($this->configureFieldMapping());
        $hydrated = [];

        foreach ($items as $iKey => $item) {

            $hydrated[$iKey] = [];

            foreach ($item as $name => $raw) {

                if (!isset($mapping[$name])) {
                    continue;
                }

                $class = $mapping[$name];

                $field = $this->createField($class, $name, $raw, (int) $iKey);

                if ($field !== null) {
                    $hydrated[$iKey][$name] = $field;
                }
            }
        }

        return $hydrated;
    }
    
    protected function localizeItems(array $items, BlockEntityInterface $entity): array
    {
        $locale = $entity->locale();
        $translatable = $this->configureTranslatableFields();
        $localized = [];

        foreach ($items as $iKey => $item) {
            $localized[$iKey] = [];

            foreach ($item as $name => $raw) {

                // Only localize fields explicitly marked as translatable
                if (in_array($name, $translatable, true)) {

                    // Same logic as BlockEntity::localized()
                    if (is_string($raw)) {
                        $localized[$iKey][$name] = $raw;
                        continue;
                    }

                    if ($raw instanceof StringTranslations) {
                        $localized[$iKey][$name] = $raw->get(locale: $locale);
                        continue;
                    }

                    if (!is_array($raw)) {
                        $localized[$iKey][$name] = '';
                        continue;
                    }

                    if (isset($raw[$locale]) && is_string($raw[$locale])) {
                        $localized[$iKey][$name] = $raw[$locale];
                        continue;
                    }

                    $firstKey = (string)array_key_first($raw);
                    $value = $raw[$firstKey] ?? '';
                    $localized[$iKey][$name] = is_string($value) ? $value : '';
                    continue;
                }

                // Non-translatable field → raw value untouched
                $localized[$iKey][$name] = $raw;
            }
        }

        return $localized;
    }

    /**
     * Dispatches the mapped BlockField class to its dedicated
     * creation method. Unknown classes are silently skipped.
     *
     * @param class-string<FieldInterface> $class
     * @param string $name
     * @param mixed $raw
     * @param int $itemIndex
     * @return null|FieldInterface
     */
    protected function createField(string $class, string $name, mixed $raw, int $itemIndex): null|FieldInterface
    {
        return match ($class) {
            Field\Data::class => $this->createDataField($name, $raw),
            Field\File::class => $this->createFileField($name, $raw),
            Field\Html::class => $this->createHtmlField($name, $raw),
            Field\HtmlTextEditor::class => $this->createHtmlTextEditorField($name, $raw, $itemIndex),
            Field\Image::class => $this->createImageField($name, $raw),
            Field\Text::class => $this->createTextField($name, $raw),
            default => null,
        };
    }

    /**
     * Creates a Data field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @return FieldInterface
     */
    protected function createDataField(string $name, mixed $raw): FieldInterface
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
     * @return FieldInterface
     */
    protected function createFileField(string $name, mixed $raw): FieldInterface
    {
        $file = is_array($raw) ? $raw : [];
        
        return new Field\File(
            view: $this->view,
            file: new Item(
                attributes: $file,
                locale: 'en',
                localeFallbacks: [],
            ),
            definition: $this->configureImageDefinitions()[$name] ?? 'block-field-file',
            generateImagesInBackground: $this->generateImagesInBackground,
        );
    }
    
    /**
     * Creates a Html field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @return FieldInterface
     */
    protected function createHtmlField(string $name, mixed $raw): FieldInterface
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
     * @param int $itemIndex
     * @return FieldInterface
     */
    protected function createHtmlTextEditorField(string $name, mixed $raw, int $itemIndex): FieldInterface
    {
        $toolbar = $this->configureTextEditorToolbars()[$name] ?? [];
        
        return new Field\HtmlTextEditor(
            html: is_string($raw) ? $raw : '',
            field: $name,
            translatable: in_array($name, $this->configureTranslatableFields()),
            itemIndex: $itemIndex,
            toolbar: $toolbar,
            view: $this->view,
        );
    }

    /**
     * Creates an Image field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @return FieldInterface
     */
    protected function createImageField(string $name, mixed $raw): FieldInterface
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
        
        $definition = $this->configureImageDefinitions()[$name] ?? 'block-field-image';
        
        return new Field\Image(
            pictureGenerator: $this->getService(PictureGeneratorInterface::class),
            view: $this->view,
            path: $raw['src'] ?? '',
            resource: $raw['storage'] ?? '',
            definition: $definition,
            imgAlt: $raw['alt'] ?? '',
            imgWidth: (int)($raw['width'] ?? 300),
            figcaption: $raw['figcaption'] ?? '',
            generateImagesInBackground: $this->generateImagesInBackground,
        );        
    }
    
    /**
     * Creates a Text field instance.
     *
     * @param string $name
     * @param mixed $raw
     * @return FieldInterface
     */
    protected function createTextField(string $name, mixed $raw): FieldInterface
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