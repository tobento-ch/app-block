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
 
namespace Tobento\App\Block\Routing;

use Tobento\App\Block\Controller\BlockEditorController;
use Tobento\App\Crud\Boot\Crud;
use Tobento\App\Language\RouteLocalizerInterface;
use Tobento\Service\Routing\RouterInterface;

/**
 * BlockEditorRoutes
 */
class BlockEditorRoutes
{
    /**
     * Invoke routes.
     *
     * @param Crud $crud
     * @param RouterInterface $router
     * @param RouteLocalizerInterface $routeLocalizer
     * @return void
     */
    public function __invoke(Crud $crud, RouterInterface $router, RouteLocalizerInterface $routeLocalizer): void
    {
        $crud->routeController(
            BlockEditorController::class,
            /*middleware: [
                [
                    \Tobento\App\User\Middleware\VerifyRoutePermission::class,
                    'permissions' => [
                        'blocks.index' => 'blocks',
                        'blocks.show' => 'blocks',
                        'blocks.create' => 'blocks.create',
                        'blocks.store' => 'blocks.create',
                        'blocks.copy' => 'blocks.create',
                        'blocks.edit' => 'blocks.edit',
                        'blocks.update' => 'blocks.edit',
                        'blocks.delete' => 'blocks.delete',
                        'blocks.bulk' => 'blocks.edit|blocks.delete',
                    ],
                ]
            ],*/
            localized: true,
            except: ['index', 'create', 'show', 'bulk'],
        );
        
        $name = 'block-editor';
        $router->post($name.'/store-block', [BlockEditorController::class, 'storeBlock'])
            ->name($name.'.store.block');
        
        $router->post($name.'/update-block', [BlockEditorController::class, 'updateBlock'])
            ->name($name.'.update.block');
        
        $router->post($name.'/save-block', [BlockEditorController::class, 'saveBlock'])
            ->name($name.'.save.block');
        
        $route = $router->post('{?locale}/'.$name.'/edit-block', [BlockEditorController::class, 'editBlock'])
            ->name($name.'.edit.block');
        $routeLocalizer->localizeRoute($route);
        
        $router->post($name.'/delete-block', [BlockEditorController::class, 'deleteBlock'])
            ->name($name.'.delete.block');
        
        $router->post($name.'/reorder-blocks', [BlockEditorController::class, 'reorderBlocks'])
            ->name($name.'.reorder.blocks');
    }
}