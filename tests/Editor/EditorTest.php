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
 
namespace Tobento\App\Block\Test\Editor;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\EditableBlocks;
use Tobento\App\Block\Editor\BlockFactory;
use Tobento\App\Block\Editor\Editor;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Test\Factory as F;
use Tobento\Service\View\ViewInterface;

class EditorTest extends TestCase
{
    public function testGetterMethods()
    {
        $container = F::createContainer();
        $blockFactory = new BlockFactory(container: $container, viewNamespace: null);
        $blockRepository = F::createBlockRepository();
        $editableBlocks = new EditableBlocks(container: $container);
        
        $editor = new Editor(
            name: 'foo',
            blockFactory: $blockFactory,
            blockRepository: $blockRepository,
            editableBlocks: $editableBlocks,
            view: $container->get(ViewInterface::class),
            locale: 'en',
            locales: ['en' => 'English'],
            localized: true,
        );
        
        $this->assertInstanceof(EditorInterface::class, $editor);
        $this->assertSame('foo', $editor->name());
        $this->assertSame('en', $editor->locale());
        $this->assertSame(['en' => 'English'], $editor->locales());
        $this->assertTrue($editor->localized());
        $this->assertTrue($blockFactory === $editor->getBlockFactory());
        $this->assertTrue($blockRepository === $editor->getBlockRepository());
        $this->assertTrue($editableBlocks === $editor->getEditableBlocks());
    }
}