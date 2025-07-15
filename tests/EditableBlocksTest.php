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
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Block\EditableBlocks;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Block\Test\Factory;

function trans(string $message, array $parameters = [], null|string $locale = null): string {
    return $message;
}

class EditableBlocksTest extends TestCase
{
    protected function createContainer(): ContainerInterface
    {
        $container = Factory::createContainer([
            Editable\Option\OptionsInterface::class => new Editable\Option\Options(),
        ]);
        
        return $container;
    }
    
    public function testThatImplementsEditableBlocksInterface()
    {
        $this->assertInstanceof(
            EditableBlocksInterface::class,
            new EditableBlocks($this->createContainer())
        );
    }
    
    public function testSupportsClassString()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: Editable\Text::class);
        $this->assertInstanceof(Editable\Text::class, $blocks->get('text'));
    }
    
    public function testSupportsClassInstance()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $block = new Editable\Text(new Editable\Option\Options());
        $blocks->add(name: 'text', block: $block);
        $this->assertSame($block, $blocks->get('text'));
    }
    
    public function testSupportsArray()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: [Editable\Text::class, ['options' => new Editable\Option\Options()]]);
        $this->assertInstanceof(Editable\Text::class, $blocks->get('text'));
    }
    
    public function testSortMethodSortedByTitle()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: Editable\Text::class);
        $blocks->add(name: 'hero', block: Editable\Hero::class);
        $blocks->add(name: 'persons', block: Editable\Persons::class);

        $blocksNew = $blocks->sort();
        
        $this->assertFalse($blocks === $blocksNew);
        $this->assertSame(['text', 'hero', 'persons'], $blocks->names());
        $this->assertSame(['hero', 'persons', 'text'], $blocksNew->names());
    }
    
    public function testSortMethodWithCallback()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: Editable\Text::class);
        $blocks->add(name: 'hero', block: Editable\Hero::class);
        $blocks->add(name: 'persons', block: Editable\Persons::class);

        $blocksNew = $blocks->sort(
            fn(EditableBlockInterface $a, EditableBlockInterface $b): int => $b->title() <=> $a->title()
        );
        
        $this->assertFalse($blocks === $blocksNew);
        $this->assertSame(['text', 'hero', 'persons'], $blocks->names());
        $this->assertSame(['text', 'persons', 'hero'], $blocksNew->names());
    }
    
    public function testNamesMethod()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: Editable\Text::class);
        $blocks->add(name: 'hero', block: Editable\Hero::class);
        
        $this->assertSame(['text', 'hero'], $blocks->names());
    }
    
    public function testAllMethod()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: Editable\Text::class);
        $blocks->add(name: 'hero', block: Editable\Hero::class);
        
        $this->assertSame(['text', 'hero'], array_keys($blocks->all()));
    }
    
    public function testGetIteratorMethod()
    {
        $blocks = new EditableBlocks($this->createContainer());
        $blocks->add(name: 'text', block: Editable\Text::class);
        $blocks->add(name: 'hero', block: Editable\Hero::class);
        
        $this->assertSame(['text', 'hero'], array_keys($blocks->all()));
    }
}