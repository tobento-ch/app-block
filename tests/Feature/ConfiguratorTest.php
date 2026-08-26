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
 
namespace Tobento\App\Block\Test\Feature;

use Tobento\App\AppInterface;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Middleware\BlockViewsEditor;
use Tobento\App\Block\Test\Configurator;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class ConfiguratorTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;

    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        $app->boot(App\MigrationBoot::class);
        
        $app->on(EditorFactory::class, static function(EditorFactory $factory): EditorFactory {
            return $factory->withConfigurator(new Configurator());
        })->once(false)->prototype();
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('article', function (ResponserInterface $responser) {
                return $responser->render(
                    view: 'block/test/article-configurable',
                    data: [],
                );
            })->middleware([
                BlockViewsEditor::class,
                'editorName' => 'default',
                'editable' => true,
                'resourceId' => 'article',
                'resourceGroup' => 'main',
            ]);
        });
        
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Block\Controller\BlockEditorController::class;
    }
    
    public function testStoreActionUsesConfigurator()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['type' => 'text', 'position' => 'resource', 'resource_id' => 'articles:1', 'resource_group' => 'main'],
        ]);
        
        $http->response()->assertStatus(403);
    }
    
    public function testEditActionUsesConfigurator()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/edit-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['type' => 'text', 'position' => 'resource', 'resource_id' => 'articles:1', 'resource_group' => 'main'],
        ]);
        
        $http->response()->assertStatus(403);
    }
    
    public function testUpdateActionUsesConfigurator()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/update-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => json_encode([
                'id' => 1,
                'type' => 'text',
                'status' => 'active',
                'position' => 'resource',
                'resource_id' => 'articles:2',
                'resource_group' => 'main',
            ]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'text',
            'position' => 'resource',
            'resource_id' => 'articles:1',
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(403);
    }
    
    public function testDeleteActionUsesConfigurator()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/delete-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['id' => 1, 'type' => 'text'],
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'text',
            'locale' => 'en',
            'position' => 'resource',
            'resource_id' => 'articles:1',
            'resource_group' => 'main',
            'status' => 'active',
        ]);
        
        $this->assertNotNull($this->getCrudRepository()->findById(1));
        
        $http->response()->assertStatus(403);
    }
    
    public function testReorderActionUsesConfigurator()
    {
        $http = $this->fakeHttp();

        $http->request(
            method: 'POST',
            uri: 'block-editor/reorder-blocks',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'blocks' => [
                ['id' => 1, 'sortorder' => 3],
            ],
        ]);

        $app = $this->bootingApp();

        // Create block (same as AJAX create)
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'text',
            'position' => 'resource',
            'resource_id' => 'articles:1',
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);

        // Expect configurator to block reorder
        $http->response()->assertStatus(403);
    }

    public function testAddNewBlockUsesConfigurator()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article');
        
        $response = $http->response()->assertStatus(200);
        
        $this->assertCount(1, $response->crawl()->filter('[data-block-action="add"]'));
    }
    
    public function testBlockButtonsUsesConfigurator()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article');
        
        $app = $this->bootingApp();
        $editors = $app->get(EditorsInterface::class);
        $editor = $editors->get('default');
        
        $blockRepository = $editor->getBlockRepository();
        
        $blocks = [
            ['type' => 'text', 'position' => 'resource', 'translation' => ['en' => 'Text Resource']],
        ];
        
        foreach($blocks as $block) {
            $block['editor'] = 'default';
            $block['resource_id'] = 'article';
            $block['resource_group'] = 'main';
            $blockRepository->create($block);
        }
        
        $response = $http->response()->assertStatus(200);
        
        $this->assertCount(0, $response->crawl()->filter('[data-block-action="delete"]'));
        $this->assertCount(1, $response->crawl()->filter('[data-block-action="edit"]'));
    }
}