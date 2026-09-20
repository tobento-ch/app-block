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
use function Tobento\App\Translation\trans;

/**
 * Base class for editable blocks composed of repeatable items.
 *
 * This abstraction exists to centralize the shared CRUD and
 * normalization logic required by item‑list blocks, preventing
 * duplicated implementations across blocks such as FAQ, Features,
 * and Team Members that all rely on similar item handling.
 *
 * Concrete blocks extend this class and declare their item fields
 * and options via configureItemFields().
 */
abstract class AbstractItems implements EditableBlockInterface
{
    use Traits\NormalizesFileSourceInput;

    /**
     * @param OptionsInterface $options
     */
    public function __construct(
        protected OptionsInterface $options,
    ) {}

    /**
     * Returns the configured item fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    abstract protected function configureItemFields(ActionInterface $action): iterable|FieldsInterface;
    
    /**
     * Returns the block type used for registration.
     *
     * @return string
     */
    abstract public function type(): string;
    
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
     * Returns the maximum number of items allowed.
     *
     * @return int
     */
    public function maxItems(): int
    {
        return 50;
    }

    /**
     * Returns the default number of items displayed when creating
     * a new block instance.
     *
     * @return int
     */
    public function defaultItems(): int
    {
        return 1;
    }

    /**
     * Returns the add new item text.
     *
     * @return string
     */
    public function addNewItemText(): string
    {
        return trans('Add new item');
    }
    
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
     * Returns the item fields.
     *
     * @param ActionInterface $action
     * @return Field\FieldsInterface
     */
    public function itemFields(ActionInterface $action): Field\FieldsInterface
    {
        $itemFields = [];

        foreach ($this->configureItemFields($action) as $field) {
            $itemFields[] = $field;
        }

        $this->validateItemFields($itemFields);

        return Field\Fields::fromIterable($itemFields);
    }

    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            // Item list
            new Field\Items('data.items')
                ->group(trans('Items'))
                ->fields(...$this->itemFields($action)->all())
                ->validate('maxItems:' . $this->maxItems())
                ->defaultItems(num: $this->defaultItems())
                ->addText($this->addNewItemText())
                ->withoutLabel(),
            
            // Additional block options
            ...$this->options->configureFields($action, $this),
        ];
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
        $fileSrcFields = $this->itemFields($action)->filter(
            fn(FieldInterface $f): bool => $f instanceof Field\FileSource
        );
        
        // No FileSource fields → nothing to normalize
        if ($fileSrcFields->empty()) {
            return $block;
        }

        $items = $block['data']['items'] ?? [];

        if (!is_array($items)) {
            return $block;
        }

        foreach ($items as $iKey => $item) {

            if (!is_array($item)) {
                continue;
            }
            
            /** @var iterable<Field\FileSource> $fileSrcFields */
            foreach ($fileSrcFields as $fileField) {

                $name = $fileField->name();

                // UPDATE: remove file fields so editor does not overwrite existing images
                if ($action->name() === 'update') {
                    unset($block['data']['items'][$iKey][$name]);
                    continue;
                }

                // STORE: normalize stored format → FileSource input format
                if ($action->name() === 'store') {

                    if (!isset($item[$name])) {
                        continue;
                    }

                    $value = $item[$name];

                    // Convert string path → FileSource input format
                    if (is_string($value) && $value !== '') {
                        $block['data']['items'][$iKey][$name] = [
                            'storage' => $fileField->getStorageName(),
                            'path' => $value,
                        ];
                        continue;
                    }

                    // Convert FileSource array → normalized format
                    if (is_array($value)) {
                        $block['data']['items'][$iKey][$name] = $this->normalizeFileSource(
                            file: $value,
                            srcKey: 'src',
                            storageKey: 'storage',
                        );
                    }
                }
            }
        }

        return $block;
    }
    
    protected function validateItemFields(iterable $fields): void
    {
        $allowed = [
            Field\Checkboxes::class,
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
                        'Field type "%s" is not supported inside Field\\AbstractItems.',
                        $class
                    )
                );
            }
        }
    }
}