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
use Tobento\App\Block\EditorsInterface;

class CrudBlockEditorTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        $app->boot(\Tobento\App\Block\Test\Feature\App\ArticleBoot::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Block\Test\Feature\App\ArticleController::class;
    }

    public function testCreateAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'blocks')
            ->assertBodyContains('Add Block');
    }
    
    public function testStoreAction()
    {
        // Block is set to active and existing block data is used as validated before!
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'Foo',
            'slug' => 'foo',
            'blocks' => json_encode([[
                'id' => 1,
                'type' => 'text',
                'status' => 'inactive',
                'position' => 'resource',
                'translation' => ['en' => 'Text Foo'],
            ]]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'position' => 'resource',
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(302);
        
        $entity = $this->getCrudRepository()->findById(1);
        $this->assertSame('Foo', $entity->get('title'));
        $this->assertSame('Text', $entity->get('blocks.0.translation.en'));
        $this->assertSame('active', $entity->get('blocks.0.status'));
    }
    
    public function testEditAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->bootingApp();
        $this->getCrudRepository()->create([
            'title' => 'Foo',
            'slug' => 'foo',
            'blocks' => [
                ['type' => 'text', 'position' => 'resource', 'translation' => ['en' => 'Text Resource']],
            ]
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'blocks')
            ->assertBodyContains('Add Block')
            ->assertBodyContains('Text Resource');
    }
    
    public function testUpdateAction()
    {
        // Block is set to active and existing block data is used as validated before!
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'Foo',
            'slug' => 'foo',
            'blocks' => json_encode([[
                'id' => 1,
                'type' => 'text',
                'status' => 'inactive',
                'position' => 'resource',
                'translation' => ['en' => 'Text Foo'],
            ]]),
        ]);
        
        $app = $this->bootingApp();
        $this->getCrudRepository()->create(['title' => 'Foo', 'slug' => 'foo']);
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'position' => 'resource',
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(302);
        
        $entity = $this->getCrudRepository()->findById(1);
        $this->assertSame('Foo', $entity->get('title'));
        $this->assertSame('Text', $entity->get('blocks.0.translation.en'));
        $this->assertSame('active', $entity->get('blocks.0.status'));
    }
    
    public function testDeleteAction()
    {
        // Block is set to pending!
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $app = $this->bootingApp();
        $this->getCrudRepository()->create([
            'title' => 'Foo',
            'slug' => 'foo',
            'blocks' => [
                ['id' => 1, 'type' => 'text', 'status' => 'active', 'position' => 'resource'],
            ],
        ]);
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'position' => 'resource',
            'status' => 'active',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(302);
        
        $this->assertSame(0, $this->getCrudRepository()->count());
        
        $editor = $app->get(EditorsInterface::class)->get('default');
        $block = $editor->getBlockRepository()->findById(1);;
        
        $this->assertSame('pending', $block->status());
    }
}