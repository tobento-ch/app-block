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

/**
 * EditorInterface
 */
interface EditorInterface
{
    /**
     * Returns the editor name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the locale.
     *
     * @return string
     */
    public function locale(): string;
    
    /**
     * Returns the locales.
     *
     * @return array<string, string>
     */
    public function locales(): array;
    
    /**
     * Returns whether it is localized or not.
     *
     * @return bool
     */
    public function localized(): bool;

    /**
     * Renders the editor.
     *
     * @param string $id
     * @param array<array-key, array<string, mixed>|BlockInterface|BlockEntityInterface> $blocks
     * @param array<string, mixed> $options
     * @return string
     */
    public function render(string $id, array $blocks = [], array $options = []): string;
    
    /**
     * Returns the block factory.
     *
     * @return BlockFactoryInterface
     */
    public function getBlockFactory(): BlockFactoryInterface;
    
    /**
     * Returns the block Factory.
     *
     * @return BlockRepositoryInterface
     */
    public function getBlockRepository(): BlockRepositoryInterface;
    
    /**
     * Returns the blockFactory.
     *
     * @return EditableBlocksInterface
     */
    public function getEditableBlocks(): EditableBlocksInterface;
}