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
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditableBlocks;
use Tobento\App\Block\Editor\Block\Editor as BlockEditor;
use Tobento\App\Block\Editor\BlockFactory;
use Tobento\App\Block\Editor\Editor;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\EditorFactoryInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\NullConfigurator;
use Tobento\App\Block\Test\Configurator;
use Tobento\App\Block\Test\Factory as F;
use Tobento\Service\Language\LanguageFactory;
use Tobento\Service\Language\Languages;

class EditorFactoryTest extends TestCase
{
    protected function createEditorFactory(): EditorFactoryInterface
    {
        $container = F::createContainer();
        
        return new EditorFactory(
            container: $container,
            blockRepository: F::createBlockRepository(),
            editableBlocks: null,
        );
    }

    public function testCreateEditorMethod()
    {
        $this->assertInstanceof(Editor::class, $this->createEditorFactory()->createEditor(name: 'default'));
    }
    
    public function testCreateEditorMethodLanguagesAreAppliedToRepository()
    {
        $languageFactory = new LanguageFactory();
        $languages = new Languages(
            $languageFactory->createLanguage('de', default: true, name: 'Deutsch'),
            $languageFactory->createLanguage('en', name: 'English', fallback: 'de'),
        );
        
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withLanguages($languages);
        
        $editor = $factory->createEditor(name: 'default');
        $this->assertSame('en', $editor->getBlockRepository()->getLocale());
        $this->assertSame(['en'], $editor->getBlockRepository()->getLocales());
        $this->assertSame([], $editor->getBlockRepository()->getLocaleFallbacks());
        
        $this->assertSame('en', $editor->getBlockRepository()->entityFactory()->getLocale());
        $this->assertSame(['en'], $editor->getBlockRepository()->entityFactory()->getLocales());
        $this->assertSame([], $editor->getBlockRepository()->entityFactory()->getLocaleFallbacks());
        
        $editor = $factoryNew->createEditor(name: 'default');
        $this->assertSame('de', $editor->getBlockRepository()->getLocale());
        $this->assertSame(['de', 'en'], $editor->getBlockRepository()->getLocales());
        $this->assertSame([], $editor->getBlockRepository()->getLocaleFallbacks());
        
        $this->assertSame('de', $editor->getBlockRepository()->entityFactory()->getLocale());
        $this->assertSame(['de', 'en'], $editor->getBlockRepository()->entityFactory()->getLocales());
        $this->assertSame(['en' => 'de'], $editor->getBlockRepository()->entityFactory()->getLocaleFallbacks());
    }    
    
    public function testEditableBlocksMethod()
    {
        $container = F::createContainer();
        $editableBlocks = F::createEditableBlocks();
        $factory = new EditorFactory(
            container: F::createContainer(),
            blockRepository: F::createBlockRepository(),
            editableBlocks: $editableBlocks,
        );
        
        $this->assertSame($editableBlocks, $factory->editableBlocks());
        $this->assertInstanceof(EditableBlocks::class, $this->createEditorFactory()->editableBlocks());
    }
    
    public function testBlockFactoryMethod()
    {
        $this->assertInstanceof(BlockFactory::class, $this->createEditorFactory()->blockFactory());
        $this->assertNull($this->createEditorFactory()->blockFactory()->viewNamespace());
    }
    
    public function testWithEditableBlocksMethodUsingBlocksInstance()
    {
        $editableBlocks = F::createEditableBlocks();
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withEditableBlocks($editableBlocks);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertTrue($editableBlocks === $factoryNew->editableBlocks());
    }
    
    public function testWithEditableBlocksMethodUsingArray()
    {
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withEditableBlocks([
            'hero' => Editable\Hero::class,
            'text' => [Editable\Text::class],
            'image' => new Editable\Image(new EditableOptions([])),
        ]);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertSame(['hero', 'text', 'image'], $factoryNew->editableBlocks()->names());
    }
    
    public function testAddEditableBlocksMethod()
    {
        $factory = $this->createEditorFactory();
        
        $this->assertSame([], $factory->editableBlocks()->names());
        
        $factory->addEditableBlocks([
            'hero' => Editable\Hero::class,
            'text' => [Editable\Text::class],
            'image' => new Editable\Image(new EditableOptions([])),
        ]);
        
        $this->assertSame(['hero', 'text', 'image'], $factory->editableBlocks()->names());
    }
    
    public function testAddEditableBlockMethod()
    {
        $factory = $this->createEditorFactory();
        
        $this->assertSame([], $factory->editableBlocks()->names());
        
        $factory->addEditableBlock(name: 'hero', block: Editable\Hero::class);
        $factory->addEditableBlock(name: 'text', block: [Editable\Text::class]);
        $factory->addEditableBlock(name: 'image', block: new Editable\Image(new EditableOptions([])));
        
        $this->assertSame(['hero', 'text', 'image'], $factory->editableBlocks()->names());
    }
    
    public function testWithBlockFactoryMethod()
    {
        $blockFactory = new BlockFactory(container: F::createContainer(), configurator: new NullConfigurator());
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withBlockFactory($blockFactory);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertTrue($blockFactory === $factoryNew->blockFactory());
    }
    
    public function testWithBlockFactoriesMethod()
    {
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withBlockFactories([
            'hero' => Factory\Hero::class,
            'text' => new Factory\Text(view: F::createView(), optionsFactory: new OptionsFactory([])),
            'image' => [Factory\Image::class],
        ]);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertSame(['hero', 'text', 'image'], array_keys($factoryNew->blockFactory()->getFactories()));
    }
    
    public function testAddBlockFactoriesMethod()
    {
        $factory = $this->createEditorFactory();
        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
            'text' => new Factory\Text(view: F::createView(), optionsFactory: new OptionsFactory([])),
            'image' => [Factory\Image::class],
        ]);
        
        $this->assertSame(['hero', 'text', 'image'], array_keys($factory->blockFactory()->getFactories()));
    }
    
    public function testAddBlockFactoryMethod()
    {
        $factory = $this->createEditorFactory();
        $factory->addBlockFactory(blockType: 'hero', factory: Factory\Hero::class);
        $factory->addBlockFactory(
            blockType: 'text',
            factory: new Factory\Text(view: F::createView(), optionsFactory: new OptionsFactory([]))
        );
        $factory->addBlockFactory(blockType: 'image', factory: [Factory\Image::class]);
        
        $this->assertSame(['hero', 'text', 'image'], array_keys($factory->blockFactory()->getFactories()));
    }
    
    public function testBlockRepositoryMethods()
    {
        $blockRepository = F::createBlockRepository();
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withBlockRepository($blockRepository);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertTrue($blockRepository === $factoryNew->blockRepository());
    }

    public function testConfiguratorMethods()
    {
        $configurator = new Configurator();
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withConfigurator($configurator);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertTrue($configurator === $factoryNew->configurator());
        $this->assertTrue($factoryNew->blockFactory()->configurator() === $factoryNew->configurator());
    }
    
    public function testLanguagesMethods()
    {
        $languageFactory = new LanguageFactory();
        $languages = new Languages(
            $languageFactory->createLanguage('de', default: true, name: 'Deutsch'),
        );
        
        $factory = $this->createEditorFactory();
        $factoryNew = $factory->withLanguages($languages);
        
        $this->assertFalse($factory === $factoryNew);
        $this->assertSame('en', $factory->languages()->default()->locale());
        $this->assertTrue($languages === $factoryNew->languages());
    }
}