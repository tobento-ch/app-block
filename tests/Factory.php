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

use Psr\Container\ContainerInterface;
use Tobento\App\Block\BlockEntityFactory;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\BlockStorageRepository;
use Tobento\App\Block\EditableBlocks;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Media\Picture\PictureGeneratorInterface;
use Tobento\App\Media\Picture\PictureRepositoryInterface;
use Tobento\Service\Container\Container;
use Tobento\Service\Dir\Dir;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\NullPictureTag;
use Tobento\Service\Picture\PictureTagInterface;
use Tobento\Service\Routing\Constrainer\Constrainer;
use Tobento\Service\Routing\MatchedRouteHandler;
use Tobento\Service\Routing\RequestData;
use Tobento\Service\Routing\RouteDispatcher;
use Tobento\Service\Routing\RouteFactory;
use Tobento\Service\Routing\RouteHandler;
use Tobento\Service\Routing\Router;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\Routing\RouteResponseParser;
use Tobento\Service\Routing\UrlGenerator;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Translation;
use Tobento\Service\View\Assets;
use Tobento\Service\View\Data;
use Tobento\Service\View\PhpRenderer;
use Tobento\Service\View\View;
use Tobento\Service\View\ViewInterface;

class Factory
{
    public static function createContainer(array $bindings = []): ContainerInterface
    {
        $container = new Container();
        $container->set(ViewInterface::class, static::createView());
        
        foreach($bindings as $name => $value) {
            $container->set($name, $value);
        }
        
        return $container;
    }
    
    public static function createView(): ViewInterface
    {
        $view = new View(
            new PhpRenderer(
                new Dirs(
                    new Dir(realpath(__DIR__.'/../resources/views/')),
                )
            ),
            new Data(),
            new Assets('public/assets/', 'https://www.example.com/assets/')
        );
        
        $view->addMacro('trans', function(string $message) {
            return $message;
        });
        
        $view->addMacro('sanitizeHtml', function(string $html) {
            return $html;
        });
        
        return $view;
    }
    
    public static function createBlockRepository(): BlockRepositoryInterface
    {
        return new BlockStorageRepository(
            storage: new InMemoryStorage([]),
            table: 'blocks',
            entityFactory: new BlockEntityFactory(),
        );
    }
    
    public static function createEditableBlocks(null|ContainerInterface $container = null): EditableBlocksInterface
    {
        return new EditableBlocks(container: $container ?: static::createContainer());
    }
    
    public static function createPictureGenerator(): PictureGeneratorInterface
    {
        return new class() implements PictureGeneratorInterface
        {
            public function pictureRepository(): PictureRepositoryInterface
            {
                throw new \InvalidArgumentException('Not available');
            }

            public function generate(
                string $path,
                string|ResourceInterface $resource,
                string|DefinitionInterface $definition,
                bool $queue = true,
            ): PictureTagInterface {
                return new NullPictureTag();
            }

            public function regenerate(
                string $path,
                string|ResourceInterface $resource,
                string|DefinitionInterface $definition,
                bool $queue = true,
            ): PictureTagInterface {
                return new NullPictureTag();
            }
        };
    }
    
    public static function createRouter(string $method = 'GET', string $uri = '', string $domain = 'example.com'): RouterInterface
    {
        $container = static::createContainer();
        $router = new Router(
            new RequestData($method, $uri, $domain),
            new UrlGenerator(
                'https://example.com',
                'a-random-32-character-secret-signature-key',
            ),
            new RouteFactory(),
            new RouteDispatcher($container, new Constrainer()),
            new RouteHandler($container),
            new MatchedRouteHandler($container),
            new RouteResponseParser(),
        );
        $container->set(RouterInterface::class, $router);
        return $router;
    }
    
    public static function createTranslator(): Translation\TranslatorInterface
    {
        return new Translation\Translator(
            new Translation\Resources(),
            new Translation\Modifiers(
                new Translation\Modifier\Pluralization(),
                new Translation\Modifier\ParameterReplacer(),
            ),
            new Translation\MissingTranslationHandler(),
            'en',
        );
    }    
}