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
use Tobento\App\Block\Block\Option\OptionsFactory;
use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Block;
use Tobento\App\Block\BlockEntity;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Editor\BlockFactory;
use Tobento\App\Block\Editor\Block\Editor as BlockEditor;
use Tobento\App\Block\Exception\BlockCreateException;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Test\Factory as F;

class BlockFactoryTest extends TestCase
{
    protected function createFactory(null|string $viewNamespace = null): BlockFactoryInterface
    {
        $container = F::createContainer();
        $container->set(OptionsFactoryInterface::class, new OptionsFactory());
        
        return new BlockFactory(
            container: $container,
            viewNamespace: $viewNamespace,
        );
    }

    public function testFactoryMethods()
    {
        $factory = $this->createFactory();
        $this->assertSame([], array_keys($factory->getFactories()));
        
        $factory->addFactory(blockType: 'text', factory: Factory\Text::class);
        $factory->addFactory(blockType: 'hero', factory: Factory\Hero::class);

        $this->assertSame(['text', 'hero'], array_keys($factory->getFactories()));
    }
    
    public function testViewNamespaceMethods()
    {
        $factory = $this->createFactory();
        $this->assertSame(null, $factory->viewNamespace());
        
        $factoryNew = $factory->withViewNamespace('mail');
        $this->assertFalse($factory === $factoryNew);
        $this->assertSame('mail', $factoryNew->viewNamespace());
        
        $factory = $this->createFactory(viewNamespace: 'mail');
        $this->assertSame('mail', $factory->viewNamespace());
    }
    
    public function testCreateBlockMethod()
    {
        $factory = $this->createFactory();
        $factory->addFactory(blockType: 'text', factory: Factory\Text::class);
        
        $block = $factory->createBlock([
            'type' => 'text',
            'html' => '<h1>Title</h1>',
        ]);
        
        $this->assertInstanceof(BlockEditor::class, $block);
    }
    
    public function testCreateBlockMethodUneditable()
    {
        $factory = $this->createFactory();
        $factory->addFactory(blockType: 'text', factory: Factory\Text::class);
        
        $block = $factory->createBlock([
            'type' => 'text',
            'html' => '<h1>Title</h1>',
            'editable' => false,
        ]);
        
        $this->assertInstanceof(Block\Text::class, $block);
    }
    
    public function testCreateBlockMethodThrowsBlockCreateExceptionIfBlockFactoryNotExists()
    {
        $this->expectException(BlockCreateException::class);
        
        $factory = $this->createFactory();
        
        $block = $factory->createBlock([
            'type' => 'text',
            'html' => '<h1>Title</h1>',
        ]);
    }
    
    public function testCreateBlockMethodThrowsBlockCreateExceptionIfBlockTypeNotExists()
    {
        $this->expectException(BlockCreateException::class);
        
        $factory = $this->createFactory();
        
        $block = $factory->createBlock([
            'html' => '<h1>Title</h1>',
        ]);
    }
    
    public function testCreateBlockFromEntityMethod()
    {
        $factory = $this->createFactory();
        $factory->addFactory(blockType: 'text', factory: Factory\Text::class);
        
        $block = $factory->createBlockFromEntity(new BlockEntity([
            'type' => 'text',
            'translation' => ['en' => '<h1>Title</h1>'],
        ]));
        
        $this->assertInstanceof(BlockEditor::class, $block);
    }
    
    public function testCreateBlockFromEntityMethodUneditable()
    {
        $factory = $this->createFactory();
        $factory->addFactory(blockType: 'text', factory: Factory\Text::class);
        
        $block = $factory->createBlockFromEntity(new BlockEntity([
            'type' => 'text',
            'translation' => ['en' => '<h1>Title</h1>'],
            'editable' => false,
        ]));
        
        $this->assertInstanceof(Block\Text::class, $block);
    }
    
    public function testCreateBlockFromEntityMethodThrowsBlockCreateExceptionIfBlockFactoryNotExists()
    {
        $this->expectException(BlockCreateException::class);
        
        $factory = $this->createFactory();
        
        $block = $factory->createBlockFromEntity(new BlockEntity([
            'type' => 'text',
            'translation' => ['en' => '<h1>Title</h1>'],
        ]));
    }
    
    public function testCreateBlockFromEntityMethodThrowsBlockCreateExceptionIfBlockTypeNotExists()
    {
        $this->expectException(BlockCreateException::class);
        
        $factory = $this->createFactory();
        
        $block = $factory->createBlockFromEntity(new BlockEntity([
            'translation' => ['en' => '<h1>Title</h1>'],
        ]));
    }
}