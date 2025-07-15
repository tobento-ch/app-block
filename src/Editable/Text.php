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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

/**
 * Text
 */
final class Text implements EditableBlockInterface
{
    /**
     * Create a new Text instance.
     *
     * @param OptionsInterface $options
     */
    public function __construct(
        private OptionsInterface $options,
    ) {}
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Text');
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Add a text section.');
    }
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19 10h-14" /><path d="M5 6h14" /><path d="M14 14h-9" /><path d="M5 18h6" /><path d="M18 15v6" /><path d="M15 18h6" /></svg>';
    }
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => 'text', 'translation' => ['en' => '']];
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
            Field\Textarea::new(name: 'translation', label: trans('Text'))
                ->group(trans('Text'))
                ->validate('string')
                ->translatable(),
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
        return $block;
    }
}