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
 * Hero
 */
class Hero implements EditableBlockInterface
{
    use Traits\NormalizesFileSourceInput;
    
    /**
     * Create a new Hero instance.
     *
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-hero'],
    ) {}
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Hero');
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('A Hero section to get users attention, ideally with a call to action button.');
    }
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 15h16" /><path d="M4 4m0 1a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v4a1 1 0 0 1 -1 1h-4a1 1 0 0 1 -1 -1z" /><path d="M4 20h12" /></svg>';
    }
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => 'hero', 'translation' => ['en' => '']];
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
            new Field\File(name: 'data.image', label: trans('Image'))
                ->group(trans('Image'))
                ->translatable()
                ->fileSource(function(Field\FileSource $fs): void {
                    $fs->storage(name: 'uploads-public');
                    $fs->allowedExtensions('jpg', 'png', 'webp');
                    $fs->pictureEditor(template: 'default', definitions: $this->pictureDefinitions);
                })
                ->fields(
                    new Field\Text('alt', trans('Alternative Text'))
                        ->validate('string|htmlclean')
                        ->translatable(),
                )
                ->storeFilenameTo('alt'),
            new Field\Textarea(name: 'translation', label: trans('Text'))
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
        if ($action->name() === 'update') {
            unset($block['data']['image']['src']);
        }
        
        // STORE: convert stored format to FileSource input format
        if ($action->name() === 'store') {

            if (isset($block['data']['image']) && is_array($block['data']['image'])) {
                $block['data']['image'] = $this->normalizeFileSource($block['data']['image']);
            }

            return $block;
        }
        
        return $block;
    }
}