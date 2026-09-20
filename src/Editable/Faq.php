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
 * FAQ Editable Block
 *
 * Defines the CRUD/editor fields for FAQ items.
 */
class Faq extends AbstractItems
{
    /**
     * @param OptionsInterface $options
     * @param array<array-key, string> $defaultFieldNames
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $defaultFieldNames = ['open'],
    ) {}
    
    /**
     * Returns the configured item fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureItemFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Text(name: 'question', label: trans('Question'))
            ->validate('required|htmlclean|maxLen:250')
            ->translatable();

        yield new Field\Textarea(name: 'answer', label: trans('Answer'))
            ->validate('required|string|maxLen:5000')
            ->translatable();
        
        if (in_array('open', $this->defaultFieldNames)) {
            yield new Field\Radios(name: 'open', label: trans('Start Open'))
                ->options(['0' => trans('No'), '1' => trans('Yes')])
                ->selected(value: '0', action: 'create|edit')
                ->displayInline()
                ->optionalText('');
        }
    }

    /**
     * Returns the block type used for registration.
     *
     * @return string
     */
    public function type(): string
    {
        return 'faq';
    }

    /**
     * Returns the block title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('FAQ');
    }

    /**
     * Returns the block description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Frequently asked questions.');
    }

    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M8 8a3.5 3 0 0 1 3.5 -3h1a3.5 3 0 0 1 3.5 3a3 3 0 0 1 -2 3a3 4 0 0 0 -2 4" /><path d="M12 19l0 .01" /></svg>';
    }
}