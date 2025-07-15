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
 * ImageGallery
 */
class ImageGallery implements EditableBlockInterface
{
    /**
     * Create a new ImageGallery instance.
     *
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     * @param int $maxNumberOfImages
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-image-gallery'],
        protected int $maxNumberOfImages = 50,
    ) {}
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Image Gallery');
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Add a collection of images displayed as an image gallery.');
    }
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 3m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 7.26a2.005 2.005 0 0 0 -1.012 1.737v10c0 1.1 .9 2 2 2h10c.75 0 1.158 -.385 1.5 -1" /><path d="M17 7h.01" /><path d="M7 13l3.644 -3.644a1.21 1.21 0 0 1 1.712 0l3.644 3.644" /><path d="M15 12l1.644 -1.644a1.21 1.21 0 0 1 1.712 0l2.644 2.644" /></svg>';
    }
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => 'image-gallery'];
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
            Field\Files::new(name: 'data.images', label: trans('Images'))
                ->group(trans('Images'))
                ->numberOfFiles(max: $this->maxNumberOfImages)
                ->file(function(Field\File $file): void {
                    //$file->translatable();
                    $file->fileSource(function(Field\FileSource $fs): void {
                        $fs->allowedExtensions('jpg', 'png', 'webp');
                        $fs->pictureEditor(template: 'default', definitions: $this->pictureDefinitions);
                    });
                    //$file->storeFilenameTo('alt');
                })
                ->fields(
                    Field\Text::new('alt', trans('Alternative Text'))
                        ->validate('string|htmlclean')
                        ->translatable(),
                    Field\Text::new('figcaption', trans('A caption for the photo.'))
                        ->validate('string|htmlclean')
                        ->translatable(),
                ),
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
            
            $images = $block['data']['images'] ?? [];
            
            if (!is_array($images)) {
                $images = [];
            }
            
            foreach(array_keys($images) as $key) {
                unset($block['data']['images'][$key]['src']);
            }
        }
        
        return $block;
    }
}