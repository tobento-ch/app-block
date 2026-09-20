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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

/**
 * Hero Editable Block
 */
class Hero extends AbstractFields
{
    /**
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-hero'],
    ) {}
    
    /**
     * Returns the block type used for registration.
     *
     * @return string
     */
    public function type(): string
    {
        return 'hero';
    }
    
    /**
     * Returns the configured block fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureBlockFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\File(name: 'data.image', label: trans('Image'))
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
            ->storeFilenameTo('alt');

        yield new Field\Textarea(name: 'translation', label: trans('Text'))
            ->group(trans('Text'))
            ->validate('string')
            ->translatable();
    }

    /**
     * Returns the block title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Hero');
    }

    /**
     * Returns the block description.
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
}