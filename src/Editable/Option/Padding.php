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
 
namespace Tobento\App\Block\Editable\Option;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;

/**
 * Padding option.
 */
class Padding implements OptionInterface
{
    /**
     * Create a new Padding instance.
     *
     * @param array<array-key, string> $supportedPadding
     */
    public function __construct(
        protected array $supportedPadding = ['top', 'bottom', 'left', 'right'],
    ) {}
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        $groupName = $action->trans('Padding');
        
        if (in_array('top', $this->supportedPadding)) {
            yield new Field\Select('options.padding.top', $action->trans('top'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
        
        if (in_array('bottom', $this->supportedPadding)) {
            yield new Field\Select('options.padding.bottom', $action->trans('bottom'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
        
        if (in_array('left', $this->supportedPadding)) {
            yield new Field\Select('options.padding.left', $action->trans('left'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
        
        if (in_array('right', $this->supportedPadding)) {
            yield new Field\Select('options.padding.right', $action->trans('right'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
    }
    
    /**
     * Returns the padding options.
     *
     * @param ActionInterface $action
     * @return array<string, string>
     */
    protected function options(ActionInterface $action): array
    {
        return [
            'xs' => $action->trans('extra small'),
            's' => $action->trans('small'),
            'm' => $action->trans('medium'),
            'l' => $action->trans('large'),
            'xl' => $action->trans('extra large'),
        ];
    }
}