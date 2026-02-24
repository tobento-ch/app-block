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
use Tobento\App\Block\Factory\ImageGallery as ImageGalleryFactory;
use Tobento\App\Testing\Http\AssertableJson;

class ImageGalleryTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'image-g.jpg',
            content: (string)$http->getFileFactory()->createImage('image-g.jpg', 100, 200)->getStream()
        );
        
        $block = $app->make(ImageGalleryFactory::class)->createBlock([
            'images' => [
                [
                    'src' => 'image-g.jpg',
                    'storage' => 'uploads-public',
                    'alt' => ['en' => 'Image'],
                ],
            ],
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('block block-image-gallery', $rendered);
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $rendered);
        $this->assertStringContainsString('alt="Image"', $rendered);
        $this->assertStringContainsString('width="100"', $rendered);
        $this->assertStringContainsString('height="200"', $rendered);
        $this->assertStringNotContainsString('<figcaption>', $rendered);
    }
    
    public function testBlockRenderWithMailNamespace()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'uploads-public')->write(
            path: 'image-g1.jpg',
            content: (string)$http->getFileFactory()->createImage('image-g1.jpg', 50, 50)->getStream()
        );
        
        $block = $app->make(ImageGalleryFactory::class)->withViewNamespace('mail')->createBlock([
            'images' => [
                [
                    'src' => 'image-g1.jpg',
                    'storage' => 'uploads-public',
                    'alt' => ['en' => 'Image'],
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $block->render());
    }
    
    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(ImageGalleryFactory::class)->createBlock([
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<div class="pt-xs block block-image-gallery', $block->render());
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
                'type' => 'image-gallery',
                'data' => [
                    'images' => [
                        [
                            'src' => $http->getFileFactory()->createImage('image-g2.jpg', 50, 50),
                            'alt' => [
                                'en' => 'Alt',
                            ],
                            'figcaption' => [
                                'en' => 'Caption',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.data.images.0.src', value: 'image-g2.jpg')
                     ->has(key: 'block.data.images.0.alt.en', value: 'Alt')
                     ->has(key: 'block.data.images.0.figcaption.en', value: 'Caption')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('image-g2.jpg', $block->get('data.images.0.src'));
        $this->assertSame('Alt', $block->get('data.images.0.alt.en'));
        $this->assertSame('Caption', $block->get('data.images.0.figcaption.en'));
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
            'block' => ['type' => 'image-gallery'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'data.images');
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
                'type' => 'image-gallery',
                'data' => [
                    'images' => [
                        [
                            'alt' => ['en' => 'Alt New'],
                            'figcaption' => ['en' => 'Caption New'],
                        ],
                    ],
                ],
            ]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'image-gallery',
            'data' => [
                'images' => [
                    [
                        'src' => 'image.jpg',
                        'alt' => ['en' => 'Alt'],
                        'figcaption' => ['en' => 'Caption'],
                    ],
                ],
            ],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.data.images.0.src', value: 'image.jpg')
                     ->has(key: 'block.data.images.0.alt.en', value: 'Alt New')
                     ->has(key: 'block.data.images.0.figcaption.en', value: 'Caption New')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('image.jpg', $block->get('data.images.0.src'));
        $this->assertSame('Alt New', $block->get('data.images.0.alt.en'));
        $this->assertSame('Caption New', $block->get('data.images.0.figcaption.en'));
    }
}