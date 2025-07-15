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
 
namespace Tobento\App\Block\Test\Feature\Block;

use Tobento\App\AppInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Factory\Text as TextFactory;
use Tobento\App\Testing\Http\AssertableJson;

class TextTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Block\Controller\BlockEditorController::class;
    }

    public function testBlockRender()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(TextFactory::class)->createBlock([
            'html' => '<p>lorem</p>',
        ]);
        
        $this->assertStringContainsString('<div class="block block-text content">', $block->render());
        $this->assertStringContainsString('<div data-editor><p>lorem</p></div>', $block->render());
    }
    
    public function testBlockRenderUneditable()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(TextFactory::class)->createBlock([
            'html' => '<p>lorem</p>',
            'editable' => false,
        ]);
        
        $this->assertStringContainsString('<div class="block block-text content"><p>lorem</p></div>', $block->render());
    }
    
    public function testBlockRenderHtmlIsSanitized()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(TextFactory::class)->createBlock([
            'html' => '<p>lorem</p><script>alert("hi")</script>',
            'editable' => false,
        ]);
        
        $this->assertStringContainsString('<p>lorem</p>', $block->render());
    }
    
    public function testBlockRenderWithMailNamespace()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(TextFactory::class)->withViewNamespace('mail')->createBlock([
            'html' => '<p>lorem</p>',
        ]);
        
        $this->assertStringContainsString('<div class="block block-text content">', $block->render());
        $this->assertStringContainsString('<div data-editor><p>lorem</p></div>', $block->render());
    }
    
    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(TextFactory::class)->createBlock([
            'html' => '<p>lorem</p>',
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<div class="pt-xs block block-text content">', $block->render());
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
            'block' => [
                'type' => 'text',
                'translation' => ['en' => 'Text Foo'],
            ],
        ]);
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
                     ->has(key: 'block.translation.en', value: 'Text Foo')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('Text Foo', $block->get('translation')->get());
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
            'block' => ['type' => 'text'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'translation');
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
                'translation' => ['en' => 'Text Foo'],
            ]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'text',
            'translation' => ['en' => 'Text'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.translation.en', value: 'Text Foo')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('Text Foo', $block->get('translation')->get());
    }
}