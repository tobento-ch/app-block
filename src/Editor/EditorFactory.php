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
use Tobento\App\Block\BlockEntityFactory;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\BlockStorageRepository;
use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Block\EditableBlocks;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Block\EditorFactoryInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\Languages;
use Tobento\Service\Language\LanguagesInterface;
use Tobento\Service\Repository\Storage\LocalesAware;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\View\ViewInterface;

/**
 * EditorFactory
 */
class EditorFactory implements EditorFactoryInterface
{
    /**
     * @var BlockRepositoryInterface
     */
    protected BlockRepositoryInterface $blockRepository;
    
    /**
     * @var EditableBlocksInterface
     */
    protected EditableBlocksInterface $editableBlocks;
    
    /**
     * @var BlockFactoryInterface
     */
    protected BlockFactoryInterface $blockFactory;
    
    /**
     * @var null|LanguagesInterface
     */
    protected null|LanguagesInterface $languages = null;
    
    /**
     * Create a new EditorFactory instance.
     *
     * @param ContainerInterface $container
     * @param BlockRepositoryInterface $blockRepository
     * @param null|EditableBlocksInterface $editableBlocks
     */
    final public function __construct(
        protected ContainerInterface $container,
        BlockRepositoryInterface $blockRepository,
        null|EditableBlocksInterface $editableBlocks = null,
    ) {
        $this->blockRepository = $blockRepository;
        $this->editableBlocks = $editableBlocks ?: $this->createEditableBlocks();
        $this->blockFactory = $this->createBlockFactory();
    }
    
    /**
     * Returns the editable blocks.
     *
     * @return EditableBlocksInterface
     */
    public function editableBlocks(): EditableBlocksInterface
    {
        return $this->editableBlocks;
    }
    
    /**
     * Returns the block factory.
     *
     * @return BlockFactoryInterface
     */
    public function blockFactory(): BlockFactoryInterface
    {
        return $this->blockFactory;
    }
    
    /**
     * Returns a new instance with the editable blocks.
     *
     * @param array<string, string|array|EditableBlockInterface>|EditableBlocksInterface $blocks
     * @return static
     */
    public function withEditableBlocks(array|EditableBlocksInterface $blocks): static
    {
        $new = clone $this;
        
        if ($blocks instanceof EditableBlocksInterface) {
            $new->editableBlocks = $blocks;
            return $new;
        }
        
        $editableBlocks = $this->createEditableBlocks();

        foreach($blocks as $name => $block) {
            $editableBlocks->add(name: $name, block: $block);
        }
        
        $new->editableBlocks = $editableBlocks;
        return $new;
    }

    /**
     * Add editable blocks.
     *
     * @param array<string, string|array|EditableBlockInterface> $blocks
     * @return static $this
     */
    public function addEditableBlocks(array $blocks): static
    {
        foreach($blocks as $name => $block) {
            $this->editableBlocks->add(name: $name, block: $block);
        }
        
        return $this;
    }
    
    /**
     * Add an editable block.
     *
     * @param string $name An unique name.
     * @param string|array|EditableBlockInterface $block
     * @return static $this
     */
    public function addEditableBlock(string $name, string|array|EditableBlockInterface $block): static
    {
        $this->editableBlocks->add(name: $name, block: $block);
        return $this;
    }
    
    /**
     * Returns a new instance with the block factory.
     *
     * @param BlockFactoryInterface $blockFactory
     * @return static
     */
    public function withBlockFactory(BlockFactoryInterface $blockFactory): static
    {
        $new = clone $this;
        $new->blockFactory = $blockFactory;
        return $new;
    }
    
    /**
     * Returns a new instance with the block factories.
     *
     * @param array<string, string|array|BlockFactoryInterface> $factories
     * @return static
     */
    public function withBlockFactories(array $factories): static
    {
        $new = clone $this;
        $blockFactory = $this->createBlockFactory();
        
        if (! $blockFactory instanceof BlockFactory) {
            return $new;
        }
        
        foreach($factories as $type => $factory) {
            $blockFactory->addFactory(blockType: $type, factory: $factory);
        }
        
        $new->blockFactory = $blockFactory;
        return $new;
    }
    
    /**
     * Adds block factories.
     *
     * @param array<string, string|array|BlockFactoryInterface> $factories
     * @return static $this
     */
    public function addBlockFactories(array $factories): static
    {
        foreach($factories as $type => $factory) {
            $this->addBlockFactory(blockType: $type, factory: $factory);
        }
        
        return $this;
    }
    
    /**
     * Add a block factory for a specific block.
     *
     * @param string $blockType
     * @param string|array|BlockFactoryInterface $factory
     * @return static $this
     */
    public function addBlockFactory(string $blockType, string|array|BlockFactoryInterface $factory): static
    {
        if ($this->blockFactory instanceof BlockFactory) {
            $this->blockFactory->addFactory(blockType: $blockType, factory: $factory);
        }
        
        return $this;
    }

    /**
     * Returns the block repository.
     *
     * @return BlockRepositoryInterface
     */
    public function blockRepository(): BlockRepositoryInterface
    {
        return $this->blockRepository;
    }
    
    /**
     * Returns a new instance with the specified block repository.
     *
     * @param BlockRepositoryInterface $blockRepository
     * @return static
     */
    public function withBlockRepository(BlockRepositoryInterface $blockRepository): static
    {
        $new = clone $this;
        $new->blockRepository = $blockRepository;
        return $new;
    }
    
    /**
     * Returns the languages.
     *
     * @return LanguagesInterface
     */
    public function languages(): LanguagesInterface
    {
        if (!is_null($this->languages)) {
            return $this->languages;
        }
        
        $languageFactory = new LanguageFactory();
        
        return $this->languages = new Languages(
            $languageFactory->createLanguage('en', default: true, name: 'English'),
        );
    }
    
    /**
     * Returns a new instance with the specified languages.
     *
     * @param LanguagesInterface $languages
     * @return static
     */
    public function withLanguages(LanguagesInterface $languages): static
    {
        $new = clone $this;
        $new->languages = $languages;
        return $new;
    }
    
    /**
     * Returns the created editor.
     *
     * @param string $name
     * @return EditorInterface
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function createEditor(string $name): EditorInterface
    {
        $languages = $this->languages();
        $defaultLanguage = $languages->default();
        
        $blockRepository = $this->blockRepository();
        
        if ($blockRepository instanceof LocalesAware) {
            $blockRepository->locale($defaultLanguage->key());
            $blockRepository->locales(...$languages->column('key'));
        }
        
        if (
            $blockRepository instanceof StorageRepository
            && $blockRepository->entityFactory() instanceof LocalesAware
        ) {
            $blockRepository->entityFactory()->locale($defaultLanguage->key());
        }
                
        return new Editor(
            name: $name,
            blockFactory: $this->blockFactory(),
            blockRepository: $blockRepository,
            editableBlocks: $this->editableBlocks(),
            view: $this->container->get(ViewInterface::class),
            locale: $defaultLanguage->key(),
            locales: $languages->column('name', 'key'),
            localized: true,
        );
    }
    
    /**
     * Returns the created editable blocks.
     *
     * @return EditableBlocksInterface
     */
    protected function createEditableBlocks(): EditableBlocksInterface
    {
        return new EditableBlocks(container: $this->container);
    }

    /**
     * Returns the created block factory.
     *
     * @return BlockFactoryInterface
     */
    protected function createBlockFactory(): BlockFactoryInterface
    {
        return new BlockFactory(container: $this->container);
    }
}