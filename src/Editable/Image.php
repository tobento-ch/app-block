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
 * Image
 */
class Image implements EditableBlockInterface
{
    /**
     * Create a new Image instance.
     *
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-image'],
    ) {}
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Image');
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Add an image.');
    }
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01" /><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z" /><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5" /><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3" /></svg>';
    }
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => 'image'];
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
            Field\File::new(name: 'data.image', label: trans('Image'))
                ->group(trans('Image'))
                ->translatable()
                ->fileSource(function(Field\FileSource $fs): void {
                    $fs->allowedExtensions('jpg', 'png', 'webp');
                    $fs->pictureEditor(template: 'default', definitions: $this->pictureDefinitions);
                })
                ->fields(
                    Field\Text::new('alt', trans('Alternative Text'))
                        ->validate('string|htmlclean')
                        ->translatable(),
                    Field\Text::new('figcaption', trans('A caption for the photo.'))
                        ->validate('string|htmlclean')
                        ->translatable(),
                    Field\Text::new('width', trans('Width'))
                        ->type('number')
                        ->validate('numeric|minNum:50|maxNum:3000'),
                )
                ->storeFilenameTo('alt'),
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
        
        return $block;
    }
}