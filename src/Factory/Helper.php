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
 
namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Block\Option\OptionsInterface;
use Tobento\Service\View\ViewInterface;

class Helper
{
    /**
     * Returns the resolved view name.
     *
     * @param ViewInterface $view
     * @param string $name The default view name.
     * @param null|string $namespace A view namespace such as 'mail'.
     * @param null|OptionsInterface $options
     * @return string
     */
    public static function resolveViewName(
        ViewInterface $view,
        string $name,
        null|string $namespace = null,
        null|OptionsInterface $options = null
    ): string {
        if ($namespace && str_starts_with($name, 'block/')) {
            $name = sprintf('block/%s/', $namespace).substr($name, 6);
        }
                
        $layout = $options?->get('layout');
        
        if (is_string($layout) && $layout !== '' && $view->exists($name.'-'.$layout)) {
            $name = $name.'-'.$layout;
        }
        
        return $name;
    }
}