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

use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Editor
 */
class Editor implements EditorInterface
{
    /**
     * Create a new Editor instance.
     *
     * @param string $name
     * @param BlockFactoryInterface $blockFactory
     * @param BlockRepositoryInterface $blockRepository
     * @param EditableBlocksInterface $editableBlocks
     * @param ViewInterface $view
     * @param string $locale
     * @param array<string, string> $locales
     * @param array<string, string> $localeFallbacks
     * @param bool $localized
     */
    public function __construct(
        protected string $name,
        protected BlockFactoryInterface $blockFactory,
        protected BlockRepositoryInterface $blockRepository,
        protected EditableBlocksInterface $editableBlocks,
        protected ViewInterface $view,
        protected string $locale = 'en',
        protected array $locales = ['en' => 'English'],
        protected array $localeFallbacks = [],
        protected bool $localized = true,
    ) {}
    
    /**
     * Returns the editor name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string
    {
        return $this->locale;
    }
    
    /**
     * Returns the locales.
     *
     * @return array<string, string>
     */
    public function locales(): array
    {
        return $this->locales;
    }
    
    /**
     * Returns the locale fallbacks.
     *
     * @return array<string, string>
     */
    public function localeFallbacks(): array
    {
        return $this->localeFallbacks;
    }
    
    /**
     * Returns whether it is localized or not.
     *
     * @return bool
     */
    public function localized(): bool
    {
        return $this->localized;
    }

    /**
     * Renders the editor.
     *
     * @param string $id
     * @param array<array-key, array<string, mixed>|BlockInterface|BlockEntityInterface> $blocks
     * @param array<string, mixed> $options
     * @return string
     */
    public function render(string $id, array $blocks = [], array $options = []): string
    {
        $options['displayAsTextarea'] ??= false;
        
        return $this->view->render(view: 'block/editor', data: [
            'editorName' => $this->name(),
            'editorId' => $id,
            'blocks' => $this->createBlocks($blocks),
            //'blockEntities' => $blocks,
            'editableBlocks' => $this->getEditableBlocks(),
            'options' => $options,
            //'locale' => $this->locale(),
        ]);
    }
    
    /**
     * Create blocks.
     *
     * @param array<array-key, array<string, mixed>|BlockInterface|BlockEntityInterface> $blocks
     * @return array<array-key, BlockInterface>
     */
    protected function createBlocks(array $blocks): array
    {
        $createdBlocks = [];
        
        foreach($blocks as $block) {
            if (is_array($block)) {
                $createdBlocks[] = $this->getBlockFactory()->createBlock(block: $block);
                continue;
            }

            if ($block instanceof BlockEntityInterface) {
                $createdBlocks[] = $this->getBlockFactory()->createBlockFromEntity(entity: $block);
                continue;
            }
            
            if ($block instanceof BlockInterface) {
                $createdBlocks[] = $block;
            }
        }
        
        return $createdBlocks;
    }
    
    /**
     * Returns the block factory.
     *
     * @return BlockFactoryInterface
     */
    public function getBlockFactory(): BlockFactoryInterface
    {
        return $this->blockFactory;
    }
    
    /**
     * Returns the block Factory.
     *
     * @return BlockRepositoryInterface
     */
    public function getBlockRepository(): BlockRepositoryInterface
    {
        return $this->blockRepository;
    }
    
    /**
     * Returns the blockFactory.
     *
     * @return EditableBlocksInterface
     */
    public function getEditableBlocks(): EditableBlocksInterface
    {
        return $this->editableBlocks;
    }
}