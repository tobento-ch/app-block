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
use Tobento\App\Block\Factory\Image as ImageFactory;
use Tobento\App\Testing\Http\AssertableJson;

class ImageTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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
            path: 'image0.jpg',
            content: (string)$http->getFileFactory()->createImage('image0.jpg', 100, 200)->getStream()
        );
        
        $block = $app->make(ImageFactory::class)->createBlock([
            'path' => 'image0.jpg',
            'resource' => 'uploads',
            'imgAlt' => 'Image',
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('block block-image', $rendered);
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $rendered);
        $this->assertStringContainsString('alt="Image"', $rendered);
        $this->assertStringContainsString('width="100"', $rendered);
        $this->assertStringContainsString('height="200"', $rendered);
        $this->assertStringNotContainsString('<figcaption>', $rendered);
    }
    
    public function testBlockRenderWithImgWidth()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'image1.jpg',
            content: (string)$http->getFileFactory()->createImage('image1.jpg', 100, 200)->getStream()
        );
        
        $block = $app->make(ImageFactory::class)->createBlock([
            'path' => 'image1.jpg',
            'resource' => 'uploads',
            'imgAlt' => 'Image',
            'imgWidth' => 50,
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $rendered);
        $this->assertStringContainsString('width="50"', $rendered);
        $this->assertStringContainsString('height="100"', $rendered);
    }

    public function testBlockRenderWithFigcaption()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'image2.jpg',
            content: (string)$http->getFileFactory()->createImage('image2.jpg', 50, 50)->getStream()
        );
        
        $block = $app->make(ImageFactory::class)->createBlock([
            'path' => 'image2.jpg',
            'resource' => 'uploads',
            'imgAlt' => 'Image',
            'figcaption' => 'Caption',
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $rendered);
        $this->assertStringContainsString('alt="Image"', $rendered);
        $this->assertStringContainsString('<figcaption>Caption</figcaption>', $rendered);
    }
    
    public function testBlockRenderWithMailNamespace()
    {
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'uploads')->write(
            path: 'image3.jpg',
            content: (string)$http->getFileFactory()->createImage('image3.jpg', 50, 50)->getStream()
        );
        
        $block = $app->make(ImageFactory::class)->withViewNamespace('mail')->createBlock([
            'path' => 'image3.jpg',
            'resource' => 'uploads',
            'imgAlt' => 'Image',
        ]);
        
        $this->assertStringContainsString('<picture><img src="data:image/jpeg;base64', $block->render());
    }
    
    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(ImageFactory::class)->createBlock([
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<div class="pt-xs block block-image', $block->render());
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
                'type' => 'image',
                'data' => [
                    'image' => [
                        'src' => [
                            'en' => $http->getFileFactory()->createImage('image4.jpg', 50, 50),
                        ],
                        'alt' => [
                            'en' => 'Alt',
                        ],
                        'figcaption' => [
                            'en' => 'Caption',
                        ],
                        'width' => '200',
                    ],
                ],
            ],
        ]);
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.data.image.src.en', value: 'image4.jpg')
                     ->has(key: 'block.data.image.alt.en', value: 'Alt')
                     ->has(key: 'block.data.image.figcaption.en', value: 'Caption')
                     ->has(key: 'block.data.image.width', value: '200')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('image4.jpg', $block->get('data.image.src.en'));
        $this->assertSame('Alt', $block->get('data.image.alt.en'));
        $this->assertSame('Caption', $block->get('data.image.figcaption.en'));
        $this->assertSame('200', $block->get('data.image.width'));
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
            'block' => ['type' => 'image'],
        ]);
        
        $http->response()
            ->assertStatus(200)
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
                'type' => 'image',
                'data' => [
                    'image' => [
                        'alt' => ['en' => 'Alt New'],
                        'figcaption' => ['en' => 'Caption New'],
                        'width' => '200',
                    ],
                ],
            ]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'image',
            'data' => [
                'image' => [
                    'src' => ['en' => 'image.jpg'],
                    'alt' => ['en' => 'Alt'],
                    'figcaption' => ['en' => 'Caption'],
                    'width' => '100',
                ],
            ],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.data.image.src.en', value: 'image.jpg')
                     ->has(key: 'block.data.image.alt.en', value: 'Alt New')
                     ->has(key: 'block.data.image.figcaption.en', value: 'Caption New')
                     ->has(key: 'block.data.image.width', value: '200')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('image.jpg', $block->get('data.image.src.en'));
        $this->assertSame('Alt New', $block->get('data.image.alt.en'));
        $this->assertSame('Caption New', $block->get('data.image.figcaption.en'));
        $this->assertSame('200', $block->get('data.image.width'));
    }
}