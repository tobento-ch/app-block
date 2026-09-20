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

namespace Tobento\App\Block\Editable;

use Tobento\App\Block\Editable\Option\OptionsInterface;
use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\Service\Collection\Arr;
use function Tobento\App\Translation\trans;

/**
 * Base class for editable blocks composed of fields.
 *
 * This abstraction exists to centralize the shared CRUD and
 * normalization logic needed by field‑based blocks, avoiding
 * duplicated implementations across blocks such as Hero, Banner,
 * Image, and Feature.
 *
 * Concrete blocks extend this class and declare their editable
 * fields via configureBlockFields().
 */
abstract class AbstractFields implements EditableBlockInterface
{
    use Traits\NormalizesFileSourceInput;

    /**
     * @param OptionsInterface $options
     */
    public function __construct(
        protected OptionsInterface $options,
    ) {}

    /**
     * Returns the block type used for registration.
     *
     * @return string
     */
    abstract public function type(): string;
    
    /**
     * Returns the configured block fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    abstract protected function configureBlockFields(ActionInterface $action): iterable|FieldsInterface;
    
    /**
     * Returns the title.
     *
     * @return string
     */
    abstract public function title(): string;

    /**
     * Returns the description.
     *
     * @return string
     */
    abstract public function description(): string;

    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    abstract public function icon(): string;
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type'  => $this->type()];
    }

    /**
     * Returns the block fields.
     *
     * @param ActionInterface $action
     * @return Field\FieldsInterface
     */
    public function blockFields(ActionInterface $action): Field\FieldsInterface
    {
        $fields = [];

        foreach ($this->configureBlockFields($action) as $field) {
            $fields[] = $field;
        }

        $this->validateFields($fields);

        return Field\Fields::fromIterable($fields);
    }

    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        foreach ($this->blockFields($action) as $blockField) {
            yield $blockField;
        }
        
        foreach ($this->options->configureFields($action, $this) as $optionField) {
            yield $optionField;
        }
    }

    /**
     * Map the block to the fields.
     *
     * @param array<string, mixed> $block
     * @param ActionInterface $action
     * @return array<string, mixed>
     */
    public function toFields(array $block, ActionInterface $action): array
    {
        $fields = Field\Fields::fromIterable($this->configureFields(action: $action));

        foreach ($fields as $field) {
            // File Source
            if ($field instanceof Field\FileSource) {
                $block = $this->normalizeFileSourceField($block, $field, $action);
                continue;
            }
            
            // Single File
            if ($field instanceof Field\File) {
                $block = $this->normalizeFileField($block, $field, $action);
                continue;
            }

            // Multi Files
            if ($field instanceof Field\Files) {
                $block = $this->normalizeFilesField($block, $field, $action);
                continue;
            }
        }

        return $block;
    }
    
    protected function normalizeFileSourceField(array $block, Field\FileSource $field, ActionInterface $action): array
    {
        $name = $field->name();
        $value = Arr::get($block, $name);

        if ($value === null) {
            return $block;
        }

        if ($action->name() === 'update') {
            return (array) Arr::delete($block, $name);
        }

        if ($action->name() === 'store') {
            if (is_string($value) && $value !== '') {
                return (array) Arr::set($block, $name, [
                    'storage' => $field->getStorageName(),
                    'path' => $value,
                ]);
            }

            if (is_array($value)) {
                return (array) Arr::set($block, $name, $this->normalizeFileSource(
                    file: $value,
                    srcKey: 'src',
                    storageKey: 'storage',
                ));
            }
        }

        return $block;
    }

    protected function normalizeFileField(array $block, Field\File $fileField, ActionInterface $action): array
    {
        $name = $fileField->name();
        $value = Arr::get($block, $name);

        if ($value === null) {
            return $block;
        }

        // UPDATE: remove file so editor does not overwrite existing file
        if ($action->name() === 'update') {
            // Remove only the file source path
            $block = (array) Arr::delete($block, $name.'.src');
            return $block;
        }

        // STORE: normalize
        if ($action->name() === 'store') {

            // string → normalized
            if (is_string($value) && $value !== '') {
                return (array) Arr::set($block, $name, [
                    'storage' => $fileField->getStorageName(),
                    'path' => $value,
                ]);
            }

            // array → normalized FileSource
            if (is_array($value)) {
                return (array) Arr::set($block, $name, $this->normalizeFileSource(
                    file: $value,
                    srcKey: 'src',
                    storageKey: 'storage',
                ));
            }
        }

        return $block;
    }

    protected function normalizeFilesField(array $block, Field\Files $filesField, ActionInterface $action): array
    {
        $name = $filesField->name(); // e.g. "data.files"
        $files = Arr::get($block, $name, []);

        if (!is_array($files)) {
            return $block;
        }

        // Detect FileSource sub-fields dynamically
        $subFields = $filesField->getRawFields();
        
        $fileSourceFields = $subFields->filter(fn(FieldInterface $f) => $f instanceof Field\FileSource);
        /** @var iterable<Field\FileSource> $fileSourceFields */
        
        foreach ($files as $key => $file) {
            // UPDATE: remove src + all FileSource sub-fields
            if ($action->name() === 'update') {

                // Remove main file src
                $block = (array) Arr::delete($block, sprintf('%s.%s.src', $name, $key));

                // Remove all preview image fields dynamically
                foreach ($fileSourceFields as $fsField) {
                    $block = (array) Arr::delete($block, sprintf('%s.%s.%s', $name, $key, $fsField->name()));
                }

                continue;
            }

            // STORE: normalize main file + all FileSource sub-fields
            if ($action->name() === 'store') {

                // Normalize main file
                $normalizedMain = $this->normalizeFileSource($file);
                
                $block = (array) Arr::set($block, sprintf('%s.%s', $name, $key), $normalizedMain);

                // Normalize preview image fields dynamically
                foreach ($fileSourceFields as $fsField) {

                    $fsName = $fsField->name();

                    if (!isset($file[$fsName])) {
                        continue;
                    }

                    $value = $file[$fsName];

                    // string → normalized
                    if (is_string($value) && $value !== '') {
                        $block = (array) Arr::set(
                            $block,
                            sprintf('%s.%s.%s', $name, $key, $fsName),
                            [
                                'storage' => $fsField->getStorageName(),
                                'path' => $value,
                            ]
                        );
                        continue;
                    }

                    // array → normalized FileSource
                    if (is_array($value)) {
                        $normalized = $this->normalizeFileSource(
                            file: $value,
                            srcKey: 'src',
                            storageKey: 'storage',
                        );

                        $block = (array) Arr::set(
                            $block,
                            sprintf('%s.%s.%s', $name, $key, $fsName),
                            $normalized
                        );
                    }
                }
            }
        }

        return $block;
    }
    
    protected function validateFields(iterable $fields): void
    {
        $allowed = [
            Field\Checkboxes::class,
            Field\File::class,
            Field\Files::class,
            Field\FileSource::class,
            Field\Html::class,
            Field\Options::class,
            Field\Radios::class,
            Field\Select::class,
            Field\SingleOptions::class,
            Field\Text::class,
            Field\Textarea::class,
            Field\Value::class,
        ];

        foreach ($fields as $field) {

            $class = $field::class;

            if (!in_array($class, $allowed, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Field type "%s" is not supported inside AbstractFields.',
                        $class
                    )
                );
            }
        }
    }
}