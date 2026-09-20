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
use Tobento\Service\Container\Container;
use Tobento\Service\Dir\Dir;
use Tobento\Service\Dir\Dirs;
use Tobento\Service\Imager\ResourceInterface;
use Tobento\Service\Picture\Generator\PictureGeneratorInterface;
use Tobento\Service\Picture\Generator\PictureRepositoryInterface;
use Tobento\Service\Picture\DefinitionInterface;
use Tobento\Service\Picture\PictureTag;
use Tobento\Service\Picture\PictureTagInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Repository\Storage\StorageEntityFactoryInterface;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
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
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Tag\Attributes;
use Tobento\Service\Tag\Tag;
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
                    // Main app views
                    new Dir(realpath(__DIR__.'/../resources/views/')),

                    // Test-only views
                    new Dir(realpath(__DIR__.'/views/')),
                )
            ),
            new Data(),
            new Assets('public/assets/', 'https://www.example.com/assets/')
        );
        
        $view->addMacro('trans', function(string $message) {
            return $message;
        });
        
        $view->addMacro('sanitizeHtml', function(string $html) {
            $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
            return $html;
        });
        
        $view->addMacro('routeUrl', function(string $route, array $params) {
            $query = http_build_query($params);
            return $route.($query ? '?'.$query : '');
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
                bool $allowPrivateStorage = false,
            ): PictureTagInterface {
                $picture = new PictureTag(
                    new Tag(name: 'picture', attributes: new Attributes(['data-definition' => is_string($definition) ? $definition : 'def'])),
                    new Tag(name: 'img', attributes: new Attributes([
                        'src' => $path,
                        'width' => '100',
                        'height' => '50',
                    ])),
                );

                return $picture;
            }

            public function regenerate(
                string $path,
                string|ResourceInterface $resource,
                string|DefinitionInterface $definition,
                bool $queue = true,
                bool $allowPrivateStorage = false,
            ): PictureTagInterface {
                return $this->generate($path, $resource, $definition, $queue, $allowPrivateStorage);
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
    
    /**
     * Create a new storage repository.
     */
    public static function createStorageRepository(
        string $table,
        iterable|ColumnsInterface $columns,
        null|StorageInterface $storage = null,
        null|StorageEntityFactoryInterface $entityFactory = null,
    ): RepositoryInterface {
        
        if (is_null($storage)) {
            $storage = new  InMemoryStorage(items: []);
        }
        
        return new class(
            $storage,
            $table,
            $columns,
            $entityFactory,
        ) extends StorageRepository {
            //
        };
    }
}