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
 
namespace Tobento\App\Block\Editor;

use Psr\Container\ContainerInterface;
use Tobento\App\Block\BlockEntity;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\App\Block\Exception\BlockCreateException;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\View\ViewInterface;
use Throwable;

/**
 * BlockFactory
 */
class BlockFactory implements BlockFactoryInterface
{
    /**
     * @var null|string
     */
    protected null|string $viewNamespace = null;
    
    /**
     * @var array<string, string|array|BlockFactoryInterface>
     */
    protected array $factories = [];
    
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * Create a new BlockFactories instance.
     *
     * @param ContainerInterface $container
     * @param ConfiguratorInterface $configurator
     * @param null|string $viewNamespace
     */
    public function __construct(
        ContainerInterface $container,
        protected ConfiguratorInterface $configurator,
        null|string $viewNamespace = null,
    ) {
        if ($viewNamespace) {
            $this->viewNamespace = $viewNamespace;
        }
        
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Add a factory for a specific block.
     *
     * @param string $blockType
     * @param string|array|BlockFactoryInterface $factory
     * @return static $this
     */
    public function addFactory(string $blockType, string|array|BlockFactoryInterface $factory): static
    {
        $this->factories[$blockType] = $factory;
        return $this;
    }
    
    /**
     * Returns the specific block factories.
     *
     * @return array<string, string|array|BlockFactoryInterface>
     */
    public function getFactories(): array
    {
        return $this->factories;
    }
    
    /**
     * Returns a new instance with the specified view namespace.
     *
     * @param null|string $namespace
     * @return static
     */
    public function withViewNamespace(null|string $namespace): static
    {
        $new = clone $this;
        $new->viewNamespace = $namespace;
        return $new;
    }
    
    /**
     * Returns the view namespace.
     *
     * @return null|string
     */
    public function viewNamespace(): null|string
    {
        return $this->viewNamespace;
    }
    
    /**
     * Create block.
     *
     * @param array<string, mixed> $block
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlock(array $block): BlockInterface
    {
        $block = $this->configurator->configureCreateBlock(block: $block);
        
        $createdBlock = $this->creatingBlock(block: $block);

        if (($block['editable'] ?? true) === false) {
            return $createdBlock;
        }
        
        try {
            return new Block\Editor(
                block: $createdBlock,
                view: $this->autowire->container()->get(ViewInterface::class),
                entity: new BlockEntity([
                    'id' => $block['id'] ?? 0,
                    'status' => $block['status'] ?? 'pending',
                    'resource_id' => $block['resource_id'] ?? null,
                    'resource_key' => $block['resource_key'] ?? null,
                    'position' => $block['position'] ?? null,
                ]),
                configurator: $this->configurator,
            );
        } catch (Throwable $e) {
            throw new BlockCreateException(block: $block, previous: $e);
        }
    }
    
    /**
     * Create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockInterface
     * @throws BlockCreateException
     */
    public function createBlockFromEntity(BlockEntityInterface $entity): BlockInterface
    {
        $entity = $this->configurator->configureCreateBlockFromEntity(entity: $entity);
        
        $createdBlock = $this->creatingBlockFromEntity(entity: $entity);
        
        if ($entity->get('editable') === false) {
            return $createdBlock;
        }
        
        try {
            return new Block\Editor(
                block: $createdBlock,
                view: $this->autowire->container()->get(ViewInterface::class),
                entity: $entity,
                configurator: $this->configurator,
            );
        } catch (Throwable $e) {
            throw new BlockCreateException(block: $entity->toArray(), previous: $e);
        }
    }
    
    /**
     * Create block.
     *
     * @param array<string, mixed> $block
     * @return BlockInterface
     * @throws BlockCreateException
     */
    protected function creatingBlock(array $block): BlockInterface
    {
        if (!isset($block['type']) || !is_string($block['type'])) {
            throw new BlockCreateException(block: $block, message: 'Missing or invalid block type.');
        }
        
        $type = $block['type'];
        
        if ($this->hasFactory(blockType: $type)) {
            return $this->getFactory(blockType: $type)->createBlock($block);
        }

        throw new BlockCreateException(block: $block, message: sprintf('Block factory for type [%s] not found.', $type));
    }
    
    /**
     * Create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockInterface
     * @throws BlockCreateException
     */
    protected function creatingBlockFromEntity(BlockEntityInterface $entity): BlockInterface
    {
        if ($this->hasFactory(blockType: $entity->type())) {
            return $this->getFactory(blockType: $entity->type())->createBlockFromEntity(entity: $entity);
        }
        
        throw new BlockCreateException(
            block: $entity->toArray(),
            message: sprintf('Block factory for type [%s] not found.', $entity->type())
        );
    }
    
    /**
     * Returns true if factory exists, otherwise false.
     *
     * @param string $blockType
     * @return bool
     */
    protected function hasFactory(string $blockType): bool
    {
        return array_key_exists($blockType, $this->factories);
    }
    
    /**
     * Returns a factory by name.
     *
     * @param string $blockType
     * @return null|BlockFactoryInterface
     */
    protected function getFactory(string $blockType): null|BlockFactoryInterface
    {
        $factory = $this->factories[$blockType] ?? null;
        
        if (is_null($factory)) {
            return null;
        }
        
        if ($factory instanceof BlockFactoryInterface) {
            return $factory;
        }
                
        if (is_array($factory)) {
            $class = $factory[0] ?? '';
            unset($factory[0]);
            $factory = $this->autowire->resolve($class, $factory);
        } else {
            $factory = $this->autowire->resolve($factory);
        }
        
        if ($factory->viewNamespace() !== $this->viewNamespace()) {
            $factory = $factory->withViewNamespace($this->viewNamespace());
        }
        
        return $this->factories[$blockType] = $factory;
    }
}