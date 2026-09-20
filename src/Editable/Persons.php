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
 * Persons Editable Block
 *
 * Defines the CRUD/editor fields for Persons items.
 */
class Persons extends AbstractItems
{
    /**
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-persons'],
    ) {}
    
    /**
     * Returns the configured item fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureItemFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Text(name: 'name', label: trans('Name'))
            ->type('text')
            ->validate('string|htmlclean|maxLen:100');
        
        yield new Field\Text(name: 'position', label: trans('Position'))
            ->type('text')
            ->validate('string|htmlclean|maxLen:100');
        
        yield new Field\Text(name: 'email', label: trans('E-Mail'))
            ->type('email')
            ->validate('email');
        
        yield new Field\Text(name: 'tel', label: trans('Telephone'))
            ->type('tel')
            ->validate('string|htmlclean|minLen:6|maxLen:20');
        
        yield new Field\FileSource(name: 'image', label: trans('Image'))
            ->storage(name: 'uploads-public')
            ->allowedExtensions('jpg', 'png', 'webp')
            ->pictureEditor(template: 'default', definitions: $this->pictureDefinitions);
    }

    /**
     * Returns the block type used for registration.
     *
     * @return string
     */
    public function type(): string
    {
        return 'persons';
    }

    /**
     * Returns the block title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Persons');
    }

    /**
     * Returns the block description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Add persons with their contact information.');
    }

    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>';
    }
}