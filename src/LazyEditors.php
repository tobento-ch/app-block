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
 
namespace Tobento\App\Block;


use Psr\Container\ContainerInterface;
use Tobento\App\Block\EditorFactoryInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Exception\EditorNotFoundException;
use Tobento\Service\Autowire\Autowire;

class LazyEditors implements EditorsInterface
{
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * Create a new LazyEditors instance.
     *
     * @param ContainerInterface $container
     * @param array<string, string|callable|EditorInterface|EditorFactoryInterface> $editors
     */
    public function __construct(
        ContainerInterface $container,
        protected array $editors = [],
    ) {
        $this->autowire = new Autowire($container);
    }

    /**
     * Returns true if editor exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->editors);
    }
    
    /**
     * Returns an editor by name.
     *
     * @param string $name
     * @return EditorInterface
     * @throws EditorNotFoundException
     */
    public function get(string $name): EditorInterface
    {
        if (!isset($this->editors[$name])) {
            throw new EditorNotFoundException(editor: $name);
        }

        if ($this->editors[$name] instanceof EditorInterface) {
            return $this->editors[$name];
        }
        
        if ($this->editors[$name] instanceof EditorFactoryInterface) {
            return $this->editors[$name] = $this->editors[$name]->createEditor(name: $name);
        }
        
        if (is_string($this->editors[$name])) {
            $this->editors[$name] = $this->autowire->resolve($this->editors[$name]);
            return $this->get($name);
        }
        
        // create editor from callable:
        if (!is_callable($this->editors[$name])) {
            throw new \InvalidArgumentException(
                sprintf('Unable to create editor %s as invalid type', $name)
            );
        }
        
        $this->editors[$name] = $this->autowire->call($this->editors[$name], ['name' => $name]);
        return $this->get($name);
    }
    
    /**
     * Returns all editor names.
     *
     * @return array<array-key, string>
     */
    public function names(): array
    {
        return array_keys($this->editors);
    }
}