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
 * Layout option.
 */
class Layout implements OptionInterface
{
    /**
     * Create a new Layout instance.
     *
     * @param array<string, string> $options
     * @param string $groupName
     */
    public function __construct(
        protected array $options,
        protected string $groupName = 'General',
    ) {}
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            Field\Select::new('options.layout', $action->trans('Layout'))
                ->group($action->trans($this->groupName))
                ->options($this->options($action))
                ->emptyOption(value: 'default', label: '---'),
        ];
    }
    
    /**
     * Returns the color options.
     *
     * @param ActionInterface $action
     * @return array<string, string>
     */
    protected function options(ActionInterface $action): array
    {
        return array_map(fn(string $name) => $action->trans($name), $this->options);
    }
}