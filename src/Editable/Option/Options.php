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
 * Options.
 */
class Options implements OptionsInterface
{
    /**
     * Create a new Options instance.
     *
     * @param array<string, OptionInterface> $options
     */
    final public function __construct(
        protected array $options = [],
    ) {}
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @param EditableBlockInterface $block
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action, EditableBlockInterface $block): iterable|FieldsInterface
    {
        $fields = [];
        
        foreach($this->options as $option) {
            foreach($option->configureFields($action) as $field) {
                if ($field instanceof FieldInterface) {
                    $fields[] = $field;
                }
            }
        }
        
        return $fields;
    }
    
    /**
     * Returns a new instance ONLY with the specified options.
     *
     * @param array<array-key, string> The options such as ['padding', 'margin']
     * @return static
     */
    public function only(array $options): static
    {
        $options = array_filter(
            $this->getOptions(),
            fn(string $key): bool => in_array($key, $options),
            ARRAY_FILTER_USE_KEY
        );
        
        return new static($options);
    }
    
    /**
     * Returns a new instance EXCEPT with the specified options.
     *
     * @param array<array-key, string> The options such as ['padding', 'margin']
     * @return static
     */
    public function except(array $options): static
    {
        $options = array_filter(
            $this->getOptions(),
            fn(string $key): bool => !in_array($key, $options),
            ARRAY_FILTER_USE_KEY
        );
        
        return new static($options);
    }
    
    /**
     * Returns a new instance with the options orderd by the specified names.
     *
     * @param string ...$name
     * @return static
     */
    public function reorder(string ...$names): static
    {
        $ordered = [];
        $options = $this->getOptions();
        
        foreach($names as $name) {
            if (isset($options[$name])) {
                $ordered[$name] = $options[$name];
                unset($options[$name]);
            }
        }
        
        foreach($options as $name => $option) {
            $ordered[$name] = $option;
        }

        return new static($ordered);
    }
    
    /**
     * Returns a new instance with the added option.
     *
     * @param string $name
     * @param OptionInterface $option
     * @return static
     */
    public function withOption(string $name, OptionInterface $option): static
    {
        return new static(array_merge($this->getOptions(), [$name => $option]));
    }

    /**
     * Returns the options.
     *
     * @return array<string, OptionInterface>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}