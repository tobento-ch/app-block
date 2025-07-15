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
 
namespace Tobento\App\Block\Test\Feature\App;

use Tobento\App\Boot;
use Tobento\App\Crud\Boot\Crud;
use Tobento\Service\Storage\InMemoryStorage;

class ArticleBoot extends Boot
{
    public const BOOT = [
        Crud::class,
    ];

    public function boot(Crud $crud)
    {
        $this->app->set(ArticleRepository::class, function() {
            $repo = new ArticleRepository(
                storage: new InMemoryStorage(items: []),
                table: 'articles',
            );
            
            $repo->locale('en');
            $repo->locales('en', 'de');
            return $repo;
        });
        
        $crud->routeController(ArticleController::class);
    }
}