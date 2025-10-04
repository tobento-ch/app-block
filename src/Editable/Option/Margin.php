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
 * Margin option.
 */
class Margin implements OptionInterface
{
    /**
     * Create a new Margin instance.
     *
     * @param array<array-key, string> $supportedMargin
     */
    public function __construct(
        protected array $supportedMargin = ['top', 'bottom', 'left', 'right'],
    ) {}
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        $groupName = $action->trans('Margin');
        
        if (in_array('top', $this->supportedMargin)) {
            yield new Field\Select('options.margin.top', $action->trans('top'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
        
        if (in_array('bottom', $this->supportedMargin)) {
            yield new Field\Select('options.margin.bottom', $action->trans('bottom'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
        
        if (in_array('left', $this->supportedMargin)) {
            yield new Field\Select('options.margin.left', $action->trans('left'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---');
        }
        
        if (in_array('right', $this->supportedMargin)) {
            yield new Field\Select('options.margin.right', $action->trans('right'))
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