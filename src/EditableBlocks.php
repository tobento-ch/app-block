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

use ArrayIterator;
use Psr\Container\ContainerInterface;
use Tobento\Service\Autowire\Autowire;
use Traversable;

/**
 * EditableBlocks
 */
final class EditableBlocks implements EditableBlocksInterface
{
    /**
     * @var array<string, string|array|EditableBlockInterface>
     */
    private array $blocks = [];
    
    /**
     * @var Autowire
     */
    private Autowire $autowire;
    
    /**
     * Create a new EditableBlocks instance.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Add a block.
     *
     * @param string $name An unique name.
     * @param string|array|EditableBlockInterface $block
     * @return static $this
     */
    public function add(string $name, string|array|EditableBlockInterface $block): static
    {
        $this->blocks[$name] = $block;
        return $this;
    }
    
    /**
     * Returns true if block exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->blocks);
    }
    
    /**
     * Returns a block by name.
     *
     * @param string $name
     * @return null|EditableBlockInterface
     */
    public function get(string $name): null|EditableBlockInterface
    {
        $block = $this->blocks[$name] ?? null;
        
        if (is_null($block)) {
            return null;
        }
        
        if ($block instanceof EditableBlockInterface) {
            return $block;
        }
        
        if (is_array($block)) {
            $class = $block[0] ?? '';
            unset($block[0]);
            return $this->blocks[$name] = $this->autowire->resolve($class, $block);
        }
        
        return $this->blocks[$name] = $this->autowire->resolve($block);
    }
    
    /**
     * Returns a new instance with the blocks sorted.
     *
     * @param null|callable $callback If null, it sorts by title.
     * @return static
     * @psalm-suppress InvalidArgument
     */
    public function sort(null|callable $callback = null): static
    {
        if (is_null($callback)) {
            $callback = fn(EditableBlockInterface $a, EditableBlockInterface $b): int
                => $a->title() <=> $b->title();
        }
        
        $new = clone $this;
        $new->all();
        uasort($new->blocks, $callback);
        return $new;
    }
    
    /**
     * Returns all block names.
     *
     * @return array<array-key, string>
     */
    public function names(): array
    {
        return array_keys($this->blocks);
    }
    
    /**
     * Returns all blocks.
     *
     * @return array<string, EditableBlockInterface>
     */
    public function all(): array
    {
        $blocks = [];
        
        foreach($this->names() as $name) {
            if (!is_null($block = $this->get($name))) {
                $blocks[$name] = $block;
            }
        }
        
        return $blocks;
    }
    
    /**
     * Get iterator.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
}