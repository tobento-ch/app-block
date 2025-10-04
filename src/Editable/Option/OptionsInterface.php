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

use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;

/**
 * OptionsInterface
 */
interface OptionsInterface
{
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @param EditableBlockInterface $block
     * @return iterable<int, FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action, EditableBlockInterface $block): iterable|FieldsInterface;
    
    /**
     * Returns a new instance ONLY with the specified options.
     *
     * @param array<array-key, string> The options such as ['padding', 'margin']
     * @return static
     */
    public function only(array $options): static;
    
    /**
     * Returns a new instance EXCEPT with the specified options.
     *
     * @param array<array-key, string> The options such as ['padding', 'margin']
     * @return static
     */
    public function except(array $options): static;
    
    /**
     * Returns a new instance with the options orderd by the specified names.
     *
     * @param string ...$names
     * @return static
     */
    public function reorder(string ...$names): static;
    
    /**
     * Returns a new instance with the added option.
     *
     * @param string $name
     * @param OptionInterface $option
     * @return static
     */
    public function withOption(string $name, OptionInterface $option): static;

    /**
     * Returns the options.
     *
     * @return array<string, OptionInterface>
     */
    public function getOptions(): array;
}