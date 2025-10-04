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
 
namespace Tobento\App\Block\Boot;

use Psr\Container\ContainerInterface;
use Tobento\App\Boot;
use Tobento\App\Boot\Config;
use Tobento\App\Block\BlockFactoriesInterface;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\LazyEditors;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Migration\Boot\Migration;
use Tobento\Service\Console\ConsoleInterface;

/**
 * Block
 */
class Block extends Boot
{
    public const INFO = [
        'boot' => [
            'installs and loads block config file',
            'implements needed interfaces',
            'add routes for the block editors',
        ],
    ];

    public const BOOT = [
        Config::class,
        Migration::class,
        Crud::class,
    ];

    /**
     * Boot application services.
     *
     * @param Config $config
     * @param Migration $migration
     * @return void
     */
    public function boot(
        Config $config,
        Migration $migration,
    ): void {
        // Migration:
        $migration->install(\Tobento\App\Block\Migration\Block::class);
        $migration->install(\Tobento\App\Media\Migration\MediaExtended::class);
        
        // Load the config:
        $config = $config->load('block.php');
        
        // Interfaces:
        foreach($config['interfaces'] ?? [] as $interface => $implementation) {
            $this->app->set($interface, $implementation);
        }
        
        // Install migration after interfaces are set:
        foreach($config['migrations'] ?? [] as $migrationClass) {
            $migration->install($migrationClass);
        }
        
        // Editors:
        $this->app->set(
            EditorsInterface::class,
            static function (ContainerInterface $container) use ($config): EditorsInterface {
                return new LazyEditors(
                    container: $container,
                    editors: $config['editors'] ?? [],
                );
            }
        );
        
        // Console commands:
        $this->app->on(ConsoleInterface::class, static function(ConsoleInterface $console): void {
            $console->addCommand(\Tobento\App\Block\Console\PurgeBlocksCommand::class);
        });
        
        // Routing:
        if ($routes = $config['routes'] ?? null) {
            $this->app->call($routes);
        }
    }
}