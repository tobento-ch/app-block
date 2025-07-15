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
use Tobento\App\Block\Factory\Hero as HeroFactory;
use Tobento\App\Testing\Http\AssertableJson;

class HeroTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\FileStorage\RefreshFileStorages;
    
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
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'image-h1.jpg',
            content: (string)$http->getFileFactory()->createImage('image-h1.jpg', 50, 50)->getStream()
        );
        
        $block = $app->make(HeroFactory::class)->createBlock([
            'html' => '<p>lorem</p>',
            'path' => 'image-h1.jpg',
            'resource' => 'uploads',
            'imgAlt' => 'Image',
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('block block-hero', $rendered);
        $this->assertStringContainsString('data-editor', $rendered);
        $this->assertStringContainsString('<p>lorem</p>', $rendered);
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $rendered);
        $this->assertStringContainsString('alt="Image"', $rendered);
    }
    
    public function testBlockRenderUneditable()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(HeroFactory::class)->createBlock([
            'editable' => false,
        ]);
        
        $this->assertStringNotContainsString('data-editor', $block->render());
    }
    
    public function testBlockRenderHtmlIsSanitized()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(HeroFactory::class)->createBlock([
            'html' => '<p>lorem</p><script>alert("hi")</script>',
        ]);
        
        $this->assertStringContainsString('<p>lorem</p>', $block->render());
    }
    
    public function testBlockRenderWithMailNamespace()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(HeroFactory::class)->withViewNamespace('mail')->createBlock([
            'html' => '<p>lorem</p>',
        ]);
        
        $this->assertStringContainsString('<p>lorem</p>', $block->render());
    }
    
    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(HeroFactory::class)->createBlock([
            'html' => '<p>lorem</p>',
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<div class="pt-xs block block-hero', $block->render());
    }
    
    public function testStoreAction()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => [
                'type' => 'hero',
                'translation' => ['en' => 'Text Foo'],
                'data' => [
                    'image' => [
                        'src' => [
                            'en' => $http->getFileFactory()->createImage('image-h2.jpg', 50, 50),
                        ],
                        'alt' => [
                            'en' => 'Alt',
                        ],
                    ],
                ],
            ],
        ]);
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
                     ->has(key: 'block.translation.en', value: 'Text Foo')
                     ->has(key: 'block.data.image.src.en', value: 'image-h2.jpg')
                     ->has(key: 'block.data.image.alt.en', value: 'Alt')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('Text Foo', $block->get('translation')->get());
        $this->assertSame('image-h2.jpg', $block->get('data.image.src.en'));
        $this->assertSame('Alt', $block->get('data.image.alt.en'));
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
            'block' => ['type' => 'hero'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'translation')
            ->assertCrudFormFieldExists(field: 'data.image');
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
                'type' => 'hero',
                'translation' => ['en' => 'Text New'],
                'data' => [
                    'image' => [
                        'alt' => ['en' => 'Alt New'],
                    ],
                ],
            ]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'hero',
            'translation' => ['en' => 'Text'],
            'data' => [
                'image' => [
                    'src' => ['en' => 'image.jpg'],
                    'alt' => ['en' => 'Alt'],
                ],
            ],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.translation.en', value: 'Text New')
                     ->has(key: 'block.data.image.src.en', value: 'image.jpg')
                     ->has(key: 'block.data.image.alt.en', value: 'Alt New')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('Text New', $block->get('translation')->get());
        $this->assertSame('image.jpg', $block->get('data.image.src.en'));
        $this->assertSame('Alt New', $block->get('data.image.alt.en'));
    }
}