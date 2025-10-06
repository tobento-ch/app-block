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
use Tobento\App\Block\BlockEntity;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\Service\Repository\Storage\Attribute\StringTranslations;

class BlockEntityTest extends TestCase
{
    public function testThatImplementsBlockEntityInterface()
    {
        $this->assertInstanceof(BlockEntityInterface::class, new BlockEntity());
    }
    
    public function testIdMethod()
    {
        $this->assertSame(0, (new BlockEntity([]))->id());
        $this->assertSame(3, (new BlockEntity(['id' => 3]))->id());
        $this->assertSame(3, (new BlockEntity(['id' => '3']))->id());
    }
    
    public function testTypeMethod()
    {
        $this->assertSame('', (new BlockEntity([]))->type());
        $this->assertSame('foo', (new BlockEntity(['type' => 'foo']))->type());
    }
    
    public function testStatusMethod()
    {
        $this->assertSame('', (new BlockEntity([]))->status());
        $this->assertSame('foo', (new BlockEntity(['status' => 'foo']))->status());
    }
    
    public function testLocaleMethods()
    {
        $this->assertSame('', (new BlockEntity([]))->locale());
        $this->assertSame('de', (new BlockEntity(['locale' => 'de']))->locale());
        $this->assertSame('fr', (new BlockEntity(['locale' => 'de']))->setLocale('fr')->locale());
    }
    
    public function testLocaleFallbacksMethods()
    {
        $this->assertSame([], (new BlockEntity([]))->localeFallbacks());
        $this->assertSame(['de' => 'en'], (new BlockEntity(['locale_fallbacks' => ['de' => 'en']]))->localeFallbacks());
        $this->assertSame(['fr' => 'en'], (new BlockEntity(['locale_fallbacks' => ['de' => 'en']]))->setLocaleFallbacks(['fr' => 'en'])->localeFallbacks());
    }
    
    public function testResourceIdMethod()
    {
        $this->assertSame(null, (new BlockEntity([]))->resourceId());
        $this->assertSame('foo', (new BlockEntity(['resource_id' => 'foo']))->resourceId());
        $this->assertSame(null, (new BlockEntity(['resource_id' => null]))->resourceId());
        $this->assertSame(null, (new BlockEntity(['resource_id' => 125]))->resourceId());
    }
    
    public function testResourceGroupMethod()
    {
        $this->assertSame(null, (new BlockEntity([]))->resourceGroup());
        $this->assertSame('foo', (new BlockEntity(['resource_group' => 'foo']))->resourceGroup());
        $this->assertSame(null, (new BlockEntity(['resource_group' => null]))->resourceGroup());
        $this->assertSame(null, (new BlockEntity(['resource_group' => 234]))->resourceGroup());
    }
    
    public function testResourcePositionMethod()
    {
        $this->assertSame(null, (new BlockEntity([]))->position());
        $this->assertSame('foo', (new BlockEntity(['position' => 'foo']))->position());
        $this->assertSame(null, (new BlockEntity(['position' => null]))->position());
        $this->assertSame(null, (new BlockEntity(['position' => 234]))->position());
    }
    
    public function testSortorderMethod()
    {
        $this->assertSame(0, (new BlockEntity([]))->sortorder());
        $this->assertSame(0, (new BlockEntity(['sortorder' => 'foo']))->sortorder());
        $this->assertSame(0, (new BlockEntity(['sortorder' => null]))->sortorder());
        $this->assertSame(234, (new BlockEntity(['sortorder' => 234]))->sortorder());
    }
    
    public function testEditableMethods()
    {
        $this->assertSame(true, (new BlockEntity([]))->editable());
        $this->assertSame(true, (new BlockEntity(['editable' => true]))->editable());
        $this->assertSame(false, (new BlockEntity(['editable' => false]))->editable());
        $this->assertSame(true, (new BlockEntity(['editable' => false]))->setEditable(true)->editable());
        $this->assertSame(true, (new BlockEntity(['editable' => 234]))->editable());
    }
    
    public function testOptionsMethod()
    {
        $this->assertSame([], (new BlockEntity([]))->options());
        $this->assertSame([], (new BlockEntity(['options' => 'foo']))->options());
        $this->assertSame([], (new BlockEntity(['options' => null]))->options());
        $this->assertSame(['foo' => 'bar'], (new BlockEntity(['options' => ['foo' => 'bar']]))->options());
    }
    
    public function testGetMethod()
    {
        $entity = new BlockEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
        ]);
        
        $this->assertSame('Foo', $entity->get(name: 'foo'));
        $this->assertSame('Baz', $entity->get(name: 'bar.baz'));
        $this->assertSame(null, $entity->get(name: 'baz'));
        $this->assertSame('default', $entity->get(name: 'baz', default: 'default'));
    }
    
    public function testLocalizedMethod()
    {
        $entity = new BlockEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
            'translation' => new StringTranslations(
                translations: ['en' => 'red', 'de' => 'rot'],
                locale: 'en',
            ),
            'title' => ['en' => 'Title', 'de' => 'Titel']
        ]);
        
        $this->assertSame('Foo', $entity->localized(name: 'foo'));
        $this->assertSame('Foo', $entity->localized(name: 'foo', default: 'Bar'));
        $this->assertSame('Foo', $entity->localized(name: 'foo', default: 'Bar', locale: 'de'));
        
        $this->assertSame('Baz', $entity->localized(name: 'bar'));
        $this->assertSame('Baz', $entity->localized(name: 'bar.baz'));
        $this->assertSame('Baz', $entity->localized(name: 'bar.baz', default: 'Bar'));
        $this->assertSame('Baz', $entity->localized(name: 'bar.baz', default: 'Bar', locale: 'de'));
        
        $this->assertSame('', $entity->localized(name: 'translation'));
        $this->assertSame('red', $entity->localized(name: 'translation', locale: 'en'));
        $this->assertSame('rot', $entity->localized(name: 'translation', locale: 'de'));
        $this->assertSame('', $entity->localized(name: 'translation', locale: 'fr'));
        $this->assertSame('rot', $entity->setLocale('en')->localized(name: 'translation', locale: 'de'));
        
        $this->assertSame('Title', $entity->localized(name: 'title'));
        $this->assertSame('Title', $entity->localized(name: 'title', default: 'Bar'));
        $this->assertSame('Title', $entity->localized(name: 'title', default: 'Bar', locale: 'en'));
        $this->assertSame('Titel', $entity->localized(name: 'title', default: 'Bar', locale: 'de'));
    }
    
    public function testToArrayMethod()
    {
        $entity = new BlockEntity([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
            'translation' => new StringTranslations(
                translations: ['en' => 'red', 'de' => 'rot'],
                locale: 'en',
            ),
        ]);
        
        $this->assertSame([
            'foo' => 'Foo',
            'bar' => ['baz' => 'Baz'],
            'translation' => ['en' => 'red', 'de' => 'rot'],
        ], $entity->toArray());
    }
}