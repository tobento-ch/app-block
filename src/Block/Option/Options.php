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
 
namespace Tobento\App\Block\Block\Option;

use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\AttributesInterface;

/**
 * Options.
 */
class Options implements OptionsInterface
{
    /**
     * Create a new Options instance.
     *
     * @param array<string, mixed> $options
     */
    final public function __construct(
        protected array $options,
    ) {}

    /**
     * To tag attributes.
     *
     * @return AttributesInterface
     */
    public function toTagAttributes(): AttributesInterface
    {
        $classMap = $this->cssClassMap();
        $attributes = new Attributes();
        
        foreach($this->options as $optionName => $optionParams) {
            if (!is_array($optionParams)) {
                continue;
            }
            
            if ($optionName === 'classes') {
                foreach($optionParams as $classname) {
                    if ($this->isValidCssClass($classname)) {
                        $attributes->add('class', $classname);
                    }
                }
                continue;
            }
            
            foreach($optionParams as $paramName => $paramValue) {
                if (!is_string($paramName)) {
                    continue;
                }

                $value = $classMap[$optionName][$paramName][$paramValue] ?? null;
                
                if (is_string($value)) {
                    $attributes->add('class', $value);
                }
            }
        }
        
        return $attributes;
    }

    /**
     * Returns an option by name.
     *
     * @param string $name
     * @return mixed
     */
    public function get(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }
    
    /**
     * Returns all options.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->options;
    }

    /**
     * Returns true if given class name is valid, otherwise false.
     *
     * @param mixed $name
     * @return bool
     */
    protected function isValidCssClass(mixed $name): bool
    {
        if (!is_string($name)) {
            return false;
        }
        
        if ((bool) preg_match('/^[a-zA-Z0-9-_]+$/u', $name) === false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns css class map.
     *
     * @return array<string, mixed>
     */
    protected function cssClassMap(): array
    {
        return [
            'padding' => [
                'top' => ['xs' => 'pt-xs', 's' => 'pt-s', 'm' => 'pt-m', 'l' => 'pt-l', 'xl' => 'pt-xl'],
                'right' => ['xs' => 'pr-xs', 's' => 'pr-s', 'm' => 'pr-m', 'l' => 'pr-l', 'xl' => 'pr-xl'],
                'bottom' => ['xs' => 'pb-xs', 's' => 'pb-s', 'm' => 'pb-m', 'l' => 'pb-l', 'xl' => 'pb-xl'],
                'left' => ['xs' => 'pl-xs', 's' => 'pl-s', 'm' => 'pl-m', 'l' => 'pl-l', 'xl' => 'pl-xl'],                
            ],
            'margin' => [
                'top' => ['xs' => 'mt-xs', 's' => 'mt-s', 'm' => 'mt-m', 'l' => 'mt-l', 'xl' => 'mt-xl'],
                'right' => ['xs' => 'mr-xs', 's' => 'mr-s', 'm' => 'mr-m', 'l' => 'mr-l', 'xl' => 'mr-xl'],
                'bottom' => ['xs' => 'mb-xs', 's' => 'mb-s', 'm' => 'mb-m', 'l' => 'mb-l', 'xl' => 'mb-xl'],
                'left' => ['xs' => 'ml-xs', 's' => 'ml-s', 'm' => 'ml-m', 'l' => 'ml-l', 'xl' => 'ml-xl'],                
            ],
            'color' => [
                'background' => [
                    'white' => 'background-white',
                    'black' => 'background-black',
                    'primary' => 'background-primary',
                    'secondary' => 'background-secondary',
                    'success' => 'background-success',
                    'info' => 'background-info',
                    'warning' => 'background-warning',
                    'error' => 'background-error',
                    'highlight' => 'background-highlight',
                ],
                'text' => [
                    'white' => 'text-white',
                    'black' => 'text-black',
                    'primary' => 'text-primary',
                    'secondary' => 'text-secondary',
                    'success' => 'text-success',
                    'info' => 'text-info',
                    'warning' => 'text-warning',
                    'error' => 'text-error',
                    'highlight' => 'text-highlight',
                ],
            ],
        ];
    }    
}