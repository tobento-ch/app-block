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
use Tobento\App\Testing\Http\AssertableJson;

class BlockEditorControllerTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Block\Controller\BlockEditorController::class;
    }
    
    public function testIndexActionIsDisabled()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateIndexUri());
        
        $http->response()->assertStatus(404);
    }
    
    public function testCreateActionIsDisabled()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateCreateUri());
        
        $http->response()->assertStatus(404);
    }
    
    public function testShowActionIsDisabled()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateShowUri(id: 1))->query([
            'editor' => 'default',
        ]);
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }
    
    public function testEditActionIsDisabled()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: $this->generateEditUri(id: 1))->query([
            'editor' => 'default',
        ]);
        
        $this->getSeedFactory()->times(1)->create();
        
        $http->response()->assertStatus(404);
    }

    public function testStoreAction()
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
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.locale', value: 'en')
                     ->has(key: 'block.type', value: 'text')
                     ->has(key: 'block.editor', value: 'default')
                     ->has(key: 'block.resource_id', value: 'articles:1')
                     ->has(key: 'block.resource_group', value: 'main')
                     ->has(key: 'block.position', value: 'resource')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('default', $block->get('editor'));
        $this->assertSame('en', $block->locale());
        $this->assertSame('text', $block->type());
        $this->assertSame('articles:1', $block->resourceId());
        $this->assertSame('main', $block->resourceGroup());
        $this->assertSame('resource', $block->position());
    }
    
    public function testStoreActionValidatesBlockEditor()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'invalid',
            'block' => ['type' => 'text'],
        ]);
        
        $http->response()
            ->assertStatus(403)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 403)
                    ->has(key: 'message', value: 'Editor invalid not found.')
            );
    }
    
    public function testStoreActionSetsResourceIdToEmptyStringIfNotResourcePosition()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['type' => 'text', 'position' => 'header', 'resource_id' => 'articles:1'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.resource_id', value: '')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('', $block->resourceId());
    }
    
    public function testStoreActionValidatesAttributes()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => [
                'type' => 'text',
                'locale' => 'fr',
                'resource_id' => '<p>id</p>',
                'resource_group' => '<p>group</p>',
                'position' => 'resource<p></p>',
                'sortorder' => '-1',
                'status' => 'foo1',
            ],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 422)
                    ->has(key: 'messages.0.message', value: 'The locale must be one of en.')
                    ->has(key: 'messages.1.message', value: 'The resource_id contains forbidden HTML code.')
                    ->has(key: 'messages.2.message', value: 'The resource_group contains forbidden HTML code.')
                    ->has(key: 'messages.3.message', value: 'The position contains forbidden HTML code.')
                    ->has(key: 'messages.4.message', value: 'The sortorder must be at least 0.')
                    ->has(key: 'messages.5.message', value: 'The status must only contain letters [a-zA-Z]')
            );
        
        $this->assertNull($this->getCrudRepository()->findById(1));
    }
    
    public function testStoreActionValidatesBlockType()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['type' => 'unknown'],
        ]);
        
        $http->response()
            ->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 422)
                    ->has(key: 'message', value: 'Editable block unknown not found.')
            );
        
        $this->assertNull($this->getCrudRepository()->findById(1));
    }
    
    public function testEditAction()
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
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'translation');
    }
    
    public function testEditActionValidatesBlockEditor()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/edit-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'invalid',
            'block' => ['type' => 'text'],
        ]);
        
        $http->response()
            ->assertStatus(403)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 403)
                    ->has(key: 'message', value: 'Editor invalid not found.')
            );
    }
    
    public function testEditActionValidatesBlockType()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/edit-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['type' => 'unknown'],
        ]);

        $http->response()
            ->assertStatus(403)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 403)
                    ->has(key: 'message', value: 'Editable block unknown not found.')
            );
    }
    
    public function testUpdateAction()
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
                'resource_id' => 'articles:2', // cannot be updated!
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
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.locale', value: 'en')
                     ->has(key: 'block.type', value: 'text')
                     ->has(key: 'block.status', value: 'active')
                     ->has(key: 'block.editor', value: 'default')
                     ->has(key: 'block.resource_id', value: 'articles:1')
                     ->has(key: 'block.resource_group', value: 'main')
                     ->has(key: 'block.position', value: 'resource')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('default', $block->get('editor'));
        $this->assertSame('en', $block->locale());
        $this->assertSame('text', $block->type());
        $this->assertSame('articles:1', $block->resourceId());
        $this->assertSame('main', $block->resourceGroup());
        $this->assertSame('resource', $block->position());
        $this->assertSame('active', $block->status());
    }
    
    public function testUpdateActionValidatesBlockEditor()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/update-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'invalid',
            'block' => ['type' => 'text'],
        ]);
        
        $http->response()
            ->assertStatus(403)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 403)
                    ->has(key: 'message', value: 'Editor invalid not found.')
            );
    }
    
    public function testUpdateActionValidatesAttributes()
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
                'locale' => 'fr',
                'resource_group' => '<p>group</p>',
                'position' => 'resource<p></p>',
                'sortorder' => '-1',
                'status' => 'foo1',
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
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 422)
                    ->has(key: 'messages.0.message', value: 'The locale must be one of en.')
                    ->has(key: 'messages.1.message', value: 'The resource_group contains forbidden HTML code.')
                    ->has(key: 'messages.2.message', value: 'The position contains forbidden HTML code.')
                    ->has(key: 'messages.3.message', value: 'The sortorder must be at least 0.')
                    ->has(key: 'messages.4.message', value: 'The status must only contain letters [a-zA-Z]')
            );
    }
    
    public function testUpdateActionValidatesBlockId()
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
            ]),
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 404)
                    ->has(key: 'messages.0.message', value: 'Record with the ID 1 not found.')
            );
    }
    
    public function testUpdateActionValidatesBlockType()
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
                'type' => 'unknown',
            ]),
        ]);
        
        $http->response()
            ->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 422)
                    ->has(key: 'message', value: 'Editable block unknown not found.')
            );
    }
    
    public function testDeleteAction()
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
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.locale', value: 'en')
                     ->has(key: 'block.type', value: 'text')
                     ->has(key: 'block.status', value: 'active')
                     ->has(key: 'block.editor', value: 'default')
                     ->has(key: 'block.resource_id', value: 'articles:1')
                     ->has(key: 'block.resource_group', value: 'main')
                     ->has(key: 'block.position', value: 'resource')
            );
        
        $this->assertNull($this->getCrudRepository()->findById(1));
    }

    public function testDeleteActionValidatesBlockId()
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
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 404)
                    ->has(key: 'messages.0.message', value: 'Record with the ID 1 not found.')
            );
    }
    
    public function testDeleteActionValidatesBlockType()
    {
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/delete-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => ['id' => 1, 'type' => 'unknown'],
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
        
        $http->response()
            ->assertStatus(422)
            ->assertJson(fn (AssertableJson $json) =>
                $json
                    ->has(key: 'status', value: 422)
                    ->has(key: 'message', value: 'Editable block unknown not found.')
            );
        
        $this->assertNotNull($this->getCrudRepository()->findById(1));
    }
    
    public function testReorderAction()
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
                ['id' => 2, 'sortorder' => 2],
                ['id' => 3, 'sortorder' => 1],
            ],
        ]);
        
        $app = $this->bootingApp();
        
        // create blocks which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create(['editor' => 'default', 'type' => 'text', 'sortorder' => 1]);
        $editor->getBlockRepository()->create(['editor' => 'default', 'type' => 'text', 'sortorder' => 2]);
        $editor->getBlockRepository()->create(['editor' => 'default', 'type' => 'text', 'sortorder' => 3]);
                
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
            );
        
        $this->assertSame(3, $this->getCrudRepository()->findById(1)->sortorder());
        $this->assertSame(2, $this->getCrudRepository()->findById(2)->sortorder());
        $this->assertSame(1, $this->getCrudRepository()->findById(3)->sortorder());
    }
}