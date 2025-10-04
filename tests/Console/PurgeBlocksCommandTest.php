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
 
namespace Tobento\App\Block\Test\Console;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Tobento\App\Block\Block\Option\OptionsFactory;
use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\Console\PurgeBlocksCommand;
use Tobento\App\Block\Editable;
use Tobento\App\Block\Editable\Text as TextEditable;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Factory\Text as TextFactory;
use Tobento\App\Block\LazyEditors;
use Tobento\App\Block\Test\Factory;
use Tobento\App\Crud;
use Tobento\App\Crud\ActionProcessor;
use Tobento\App\Crud\ActionProcessorInterface;
use Tobento\Service\Clock\FrozenClock;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Container\Container;
use Tobento\Service\Requester\Requester;
use Tobento\Service\Requester\RequesterInterface;
use Tobento\Service\Responser\Responser;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Translation\TranslatorInterface;

require_once __DIR__.'/../trans_function.php';

class PurgeBlocksCommandTest extends TestCase
{
    protected function createContainer(): ContainerInterface
    {
        $container = Factory::createContainer([
            OptionsFactoryInterface::class => new OptionsFactory(),
            Editable\Option\OptionsInterface::class => new Editable\Option\Options(),
            BlockRepositoryInterface::class => Factory::createBlockRepository(),
        ]);
        
        $container->set(EditorsInterface::class, function(EditorFactory $factory) use ($container) {
            $factory = $factory->addEditableBlocks([
                'text' => TextEditable::class,
            ]);
            
            $factory = $factory->addBlockFactories([
                'text' => TextFactory::class,
            ]);
            
            return new LazyEditors(container: $container, editors: ['default' => $factory]);
        });        
        
        $container->set(RequesterInterface::class, function() {
            return new Requester((new Psr17Factory())->createServerRequest(
                method: 'GET',
                uri: 'https://example.com',
            ));
        });
        
        $container->set(ResponserInterface::class, function() {
            return new Responser(responseFactory: new Psr17Factory(), streamFactory: new Psr17Factory());
        });
        
        $router = Factory::createRouter();
        $router->post('block-editor/update-block', [BlockEditorController::class, 'updateBlock'])->name('block-editor.update.block');
        $router->post('block-editor/store', [BlockEditorController::class, 'store'])->name('block-editor.store');
        $router->get('block-editor/{id}/edit', [BlockEditorController::class, 'edit'])->name('block-editor.edit');
        $router->delete('block-editor/{id}', [BlockEditorController::class, 'delete'])->name('block-editor.delete');
        
        $container->set(RouterInterface::class, $router);
        $container->set(TranslatorInterface::class, Factory::createTranslator());
        
        $container->set(ActionProcessorInterface::class, function(ContainerInterface $container, RouterInterface $router) {
            return new ActionProcessor(
                container: $container,
                urlResolver: new Crud\Url\UrlResolver(router: $router),
                translator: null,
                languages: null,
            );
        });
        
        $container->set(ClockInterface::class, new FrozenClock());
        
        return $container;
    }
    
    public function testOnlyPendingBlocksAndOlderThanOneDayAreDeleted()
    {
        $container = $this->createContainer();
        $blockRepository = $container->get(BlockRepositoryInterface::class);
        
        $blockRepository->create(['editor' => 'default', 'type' => 'text', 'status' => 'pending']);
        $blockRepository->create(['editor' => 'default', 'type' => 'text', 'status' => 'active']);
        $blockRepository->create(['editor' => 'default', 'type' => 'text', 'status' => 'pending']);
        
        $this->assertSame(3, $blockRepository->count());
        
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        
        new TestCommand(
            command: PurgeBlocksCommand::class,
        )
        ->expectsOutput('Deleted block with the id 1 from editor default')
        ->expectsOutput('Deleted block with the id 3 from editor default')
        ->expectsExitCode(0)
        ->execute($container);
        
        $this->assertSame(1, $blockRepository->count());
    }
    
    public function testBlocksYoungerThanOneDayAreNotDeleted()
    {
        $container = $this->createContainer();
        $blockRepository = $container->get(BlockRepositoryInterface::class);
        
        $blockRepository->create(['editor' => 'default', 'type' => 'text', 'status' => 'pending']);
        $blockRepository->create(['editor' => 'default', 'type' => 'text', 'status' => 'active']);
        $blockRepository->create(['editor' => 'default', 'type' => 'text', 'status' => 'pending']);
        
        $this->assertSame(3, $blockRepository->count());
        
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+23 hours'));
        
        new TestCommand(
            command: PurgeBlocksCommand::class,
        )
        ->expectsExitCode(0)
        ->execute($container);
        
        $this->assertSame(3, $blockRepository->count());
    }
    
    public function testDeletingFails()
    {
        $container = $this->createContainer();
        $blockRepository = $container->get(BlockRepositoryInterface::class);
        
        $blockRepository->create(['editor' => 'default', 'type' => 'unknown', 'status' => 'pending']);
        
        $this->assertSame(1, $blockRepository->count());
        
        $container->set(ClockInterface::class, (new FrozenClock())->modify('+25 hours'));
        
        new TestCommand(
            command: PurgeBlocksCommand::class,
        )
        ->expectsOutput('Unable to delete block with the id 1 from editor default: Editable block unknown not found.')
        ->expectsExitCode(0)
        ->execute($container);
        
        $this->assertSame(1, $blockRepository->count());
    }    
}