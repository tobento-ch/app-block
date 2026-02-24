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

class CrudBlockResourceEditorTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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
            ->assertCrudFormFieldExists(field: 'resource_blocks')
            ->assertBodyContains('resource.header')
            ->assertBodyContains('resource')
            ->assertBodyContains('Add Block');
    }
    
    public function testCreateActionRestoresBlocksAfterValidationError()
    {
        $http = $this->fakeHttp();

        // Trigger validation error (title contains invalid HTML)
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'Foo<p>', // invalid
            'slug' => 'foo',
            'resource_blocks' => [json_encode([[
                'id' => 1,
                'resource_id' => 'tmp:1',
                'resource_group' => 'main',
                'type' => 'text',
                'status' => 'inactive',
                'position' => 'resource',
                'translation' => ['en' => 'Text Foo'],
            ]])],
        ]);

        $app = $this->bootingApp();

        // Simulate AJAX-created pending block
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'resource_id' => 'tmp:1',
            'resource_group' => 'main',
            'position' => 'resource',
            'status' => 'pending',
            'translation' => ['en' => 'Text Bar'],
        ]);

        // After redirect, the form should restore the pending block
        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Text Bar');
    }
    
    public function testStoreAction()
    {
        // Block is set to active and existing block data is used as validated before!
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'Foo',
            'slug' => 'foo',
            'resource_blocks' => [json_encode([[
                'id' => 1,
                'resource_id' => 'tmp:1',
                'resource_group' => 'main',
                'type' => 'text',
                'status' => 'inactive',
                'position' => 'resource',
                'translation' => ['en' => 'Text Foo'],
            ]])],
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'resource_id' => 'tmp:1',
            'resource_group' => 'main',
            'position' => 'resource',
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(302);

        $entity = $this->getCrudRepository()->findById(1);
        $this->assertSame('Foo', $entity->get('title'));
        
        $block = $editor->getBlockRepository()->findById(1);
        $this->assertSame('articles:1', $block->resourceId());
        $this->assertSame('main', $block->resourceGroup());
        $this->assertSame('Text', $block->get('translation')->get());
        $this->assertSame('active', $block->status());
    }
    
    public function testStoreActionSkipsInvalidBlocks()
    {
        // Block is set to active and existing block data is used as validated before!
        $http = $this->fakeHttp();
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'Foo',
            'slug' => 'foo',
            'resource_blocks' => [[]], // invalid
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'resource_id' => 'tmp:1',
            'resource_group' => 'main',
            'position' => 'resource',
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(302);

        $entity = $this->getCrudRepository()->findById(1);
        $this->assertSame([], $entity->get('resource_blocks'));
    }
    
    public function testEditAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1));
        
        $app = $this->bootingApp();
        $this->getCrudRepository()->create(['title' => 'Foo', 'slug' => 'foo']);
        
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'resource_id' => 'articles:1',
            'resource_group' => 'main',
            'position' => 'resource',
            'status' => 'active',
            'translation' => ['en' => 'Text Resource'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'resource_blocks')
            ->assertBodyContains('resource.header')
            ->assertBodyContains('resource')
            ->assertBodyContains('Position: resource') // custom title
            ->assertBodyContains('Add Block')
            ->assertBodyContains('Text Resource');
    }
    
    public function testCopyActionRestoresBlocksAfterValidationError()
    {
        $http = $this->fakeHttp();
        $http->previousUri($this->generateCopyUri(id: 1));
        $http->request(method: 'POST', uri: $this->generateStoreUri())->body([
            'title' => 'Foo<p>', // invalid
            'slug' => 'foo',
            'resource_blocks' => [json_encode([[
                'id' => 1,
                'resource_id' => 'tmp:1',
                'resource_group' => 'main',
                'type' => 'text',
                'status' => 'inactive',
                'position' => 'resource',
                'translation' => ['en' => 'Text Bar'],
            ]])],
        ]);

        $app = $this->bootingApp();
        $this->getCrudRepository()->create(['title' => 'Foo', 'slug' => 'foo']);

        // Simulate AJAX-created pending block
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'resource_id' => 'tmp:1',
            'resource_group' => 'main',
            'position' => 'resource',
            'status' => 'pending',
            'translation' => ['en' => 'Text Baz'],
        ]);

        $http->followRedirects()
            ->assertStatus(200)
            ->assertBodyContains('Text Baz');
    }
    
    public function testCopyAction()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCopyUri(id: 1));

        $app = $this->bootingApp();
        $this->getCrudRepository()->create([
            'title' => 'Foo',
            'slug' => 'foo',
        ]);

        // Create original resource block
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'resource_id' => 'articles:1',
            'resource_group' => 'main',
            'position' => 'resource',
            'status' => 'active',
            'translation' => ['en' => 'Text Resource'],
        ]);

        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'resource_blocks')
            ->assertBodyContains('Text Resource');

        $this->assertSame(2, $editor->getBlockRepository()->count());
    }

    public function testUpdateAction()
    {
        // Block is set to active and existing block data is used as validated before!
        $http = $this->fakeHttp();
        $http->request(method: 'PATCH', uri: $this->generateUpdateUri(id: 1))->body([
            'title' => 'Foo',
            'slug' => 'foo',
            'resource_blocks' => [json_encode([[
                'id' => 1,
                'resource_id' => 'articles:1',
                'resource_group' => 'main',
                'type' => 'text',
                'status' => 'inactive',
                'position' => 'resource',
                'translation' => ['en' => 'Text Foo'],
            ]])],
        ]);
        
        $app = $this->bootingApp();
        $this->getCrudRepository()->create(['title' => 'Foo', 'slug' => 'foo']);
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'position' => 'resource',
            'resource_id' => 'articles:1',
            'resource_group' => 'main',            
            'status' => 'pending',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()->assertStatus(302);
        
        $entity = $this->getCrudRepository()->findById(1);
        $this->assertSame('Foo', $entity->get('title'));
        
        $block = $editor->getBlockRepository()->findById(1);
        $this->assertSame('articles:1', $block->resourceId());
        $this->assertSame('main', $block->resourceGroup());
        $this->assertSame('Text', $block->get('translation')->get());
        $this->assertSame('active', $block->status());
    }
    
    public function testDeleteAction()
    {
        // Block is set to pending!
        $http = $this->fakeHttp();
        $http->request(method: 'DELETE', uri: $this->generateDeleteUri(id: 1));
        
        $app = $this->bootingApp();
        $this->getCrudRepository()->create(['title' => 'Foo', 'slug' => 'foo']);
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'position' => 'resource',
            'resource_id' => 'articles:1',
            'resource_group' => 'main',
            'status' => 'active',
        ]);
        
        $editor->getBlockRepository()->create([
            'type' => 'text',
            'position' => 'resource',
            'resource_id' => 'articles:2',
            'resource_group' => 'main',
            'status' => 'active',
        ]);
        
        $http->response()->assertStatus(302);
        
        $this->assertSame(0, $this->getCrudRepository()->count());
        
        $editor = $app->get(EditorsInterface::class)->get('default');

        $this->assertSame('pending', $editor->getBlockRepository()->findById(1)->status());
        $this->assertSame('active', $editor->getBlockRepository()->findById(2)->status());
    }
}