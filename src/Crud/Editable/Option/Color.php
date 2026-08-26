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
 * Color option.
 */
class Color implements OptionInterface
{
    /**
     * Create a new Color instance.
     *
     * @param array<array-key, string> $supportedColors
     */
    public function __construct(
        protected array $supportedColors = ['background', 'text'],
    ) {}
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        $groupName = $action->trans('Color');
        
        if (in_array('background', $this->supportedColors)) {
            yield new Field\Select('options.color.background', $action->trans('Background'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---')
                ->optionAttributes([
                    'white' => ['class' => 'background-white text-black'],
                    'black' => ['class' => 'background-black text-white'],
                    'primary' => ['class' => 'background-primary'],
                    'secondary' => ['class' => 'background-secondary'],
                    'success' => ['class' => 'background-success'],
                    'info' => ['class' => 'background-info'],
                    'warning' => ['class' => 'background-warning'],
                    'error' => ['class' => 'background-error'],
                    'highlight' => ['class' => 'background-highlight'],
                    'gray-100' => ['class' => 'background-gray-100'],
                    'gray-300' => ['class' => 'background-gray-300'],
                    'gray-500' => ['class' => 'background-gray-500'],
                    'gray-700' => ['class' => 'background-gray-700'],
                    'gray-900' => ['class' => 'background-gray-900'],
                ]);
        }
        
        if (in_array('text', $this->supportedColors)) {
            yield new Field\Select('options.color.text', $action->trans('Text'))
                ->group($groupName)
                ->options($this->options($action))
                ->emptyOption(value: 'none', label: '---')
                ->optionAttributes([
                    'white' => ['class' => 'background-white text-black'],
                    'black' => ['class' => 'background-black text-white'],
                    'primary' => ['class' => 'background-primary'],
                    'secondary' => ['class' => 'background-secondary'],
                    'success' => ['class' => 'background-success'],
                    'info' => ['class' => 'background-info'],
                    'warning' => ['class' => 'background-warning'],
                    'error' => ['class' => 'background-error'],
                    'highlight' => ['class' => 'background-highlight'],
                    'gray-100' => ['class' => 'background-gray-100'],
                    'gray-300' => ['class' => 'background-gray-300'],
                    'gray-500' => ['class' => 'background-gray-500'],
                    'gray-700' => ['class' => 'background-gray-700'],
                    'gray-900' => ['class' => 'background-gray-900'],
                ]);
        }
    }
    
    /**
     * Returns the color options.
     *
     * @param ActionInterface $action
     * @return array<string, string>
     */
    protected function options(ActionInterface $action): array
    {
        return [
            'white' => $action->trans('white'),
            'black' => $action->trans('black'),
            'primary' => $action->trans('primary'),
            'secondary' => $action->trans('secondary'),
            'success' => $action->trans('success'),
            'info' => $action->trans('info'),
            'warning' => $action->trans('warning'),
            'error' => $action->trans('error'),
            'highlight' => $action->trans('highlight'),
            'gray-100' => $action->trans('gray :weight', [':weight' => '100']),
            'gray-300' => $action->trans('gray :weight', [':weight' => '300']),
            'gray-500' => $action->trans('gray :weight', [':weight' => '500']),
            'gray-700' => $action->trans('gray :weight', [':weight' => '700']),
            'gray-900' => $action->trans('gray :weight', [':weight' => '900']),
        ];
    }
}