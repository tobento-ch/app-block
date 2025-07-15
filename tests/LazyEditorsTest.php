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
 
namespace Tobento\App\Block\Test;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\EditorFactoryInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Exception\EditorNotFoundException;
use Tobento\App\Block\LazyEditors;
use Tobento\App\Block\Test\Factory;

class LazyEditorsTest extends TestCase
{
    protected function createContainer(): ContainerInterface
    {
        $container = Factory::createContainer([
            BlockRepositoryInterface::class => Factory::createBlockRepository(),
        ]);
        
        return $container;
    }
    
    protected function createEditorFactory(): EditorFactoryInterface
    {
        return new EditorFactory(
            container: $this->createContainer(),
            blockRepository: Factory::createBlockRepository(),
            editableBlocks: null,
        );
    }
    
    public function testThatImplementsEditorsInterface()
    {
        $this->assertInstanceof(
            EditorsInterface::class,
            new LazyEditors($this->createContainer())
        );
    }
    
    public function testConstructMethod()
    {
        $editors = new LazyEditors(container: $this->createContainer(), editors: [
            'factoryClass' => EditorFactory::class,
            'factoryInstance' => $this->createEditorFactory(),
            'editorInstance' => $this->createEditorFactory()->createEditor(name: 'editorInstance'),
            'closureReturningEditorsFactory' => function (string $name) {
                return $this->createEditorFactory()->createEditor(name: $name);
            },
            'closureReturningEditor' => function (string $name) {
                return $this->createEditorFactory()->createEditor(name: $name);
            },
            'closureBeingResolved' => function (string $name, BlockRepositoryInterface $repo) {
                return $this->createEditorFactory()->createEditor(name: $name);
            },
        ]);
        
        $editors->get('factoryClass');
        $editors->get('factoryInstance');
        $editors->get('editorInstance');
        $editors->get('closureReturningEditorsFactory');
        $editors->get('closureReturningEditor');
        $editors->get('closureBeingResolved');
        
        $this->assertTrue(true);
    }
    
    public function testHasMethod()
    {
        $editors = new LazyEditors(container: $this->createContainer(), editors: [
            'foo' => EditorFactory::class,
        ]);
        
        $this->assertTrue($editors->has('foo'));
        $this->assertFalse($editors->has('bar'));
    }
    
    public function testGetMethod()
    {
        $editors = new LazyEditors(container: $this->createContainer(), editors: [
            'foo' => EditorFactory::class,
        ]);
        
        $this->assertInstanceof(EditorInterface::class, $editors->get('foo'));
    }
    
    public function testGetMethodThrowsEditorNotFoundExceptionIfNotExists()
    {
        $this->expectException(EditorNotFoundException::class);
        
        $editors = new LazyEditors(container: $this->createContainer(), editors: []);
        $this->assertInstanceof(EditorInterface::class, $editors->get('foo'));
    }
    
    public function testGetMethodThrowsInvalidArgumentExceptionIfInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to create editor foo as invalid type');
        
        $editors = new LazyEditors(container: $this->createContainer(), editors: [
            'foo' => [],
        ]);
        
        $this->assertInstanceof(EditorInterface::class, $editors->get('foo'));
    }
    
    public function testNamesMethod()
    {
        $editors = new LazyEditors(container: $this->createContainer(), editors: [
            'foo' => EditorFactory::class,
            'bar' => EditorFactory::class,
        ]);
        
        $this->assertSame(['foo', 'bar'], $editors->names('foo'));
    }
}