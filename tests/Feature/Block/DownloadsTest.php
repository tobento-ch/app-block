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
use Tobento\App\Block\Factory\Downloads as DownloadsFactory;
use Tobento\App\FileStorage\FilesystemStorageFactory;
use Tobento\App\Media\Feature;
use Tobento\App\Testing\Http\AssertableJson;
use Tobento\Service\FileStorage\StoragesInterface;
use function Tobento\App\{directory};

class DownloadsTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    use \Tobento\App\Testing\FileStorage\RefreshFileStorages;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        
        $app->on(
            StoragesInterface::class,
            function(StoragesInterface $storages, FilesystemStorageFactory $factory) {
                $storage = $factory->createStorage(name: 'downloads', config: [
                    'location' => directory('app').'storage/downloads/',
                ]);
                $storages->add($storage);
            }
        );
        
        return $app;
    }
    
    protected function getCrudController(): string
    {
        return \Tobento\App\Block\Controller\BlockEditorController::class;
    }
    
    protected function getMediaFeatures(): array
    {
        return [
            new Feature\File(
                supportedStorages: ['images', 'uploads', 'downloads'],
            ),

            new Feature\FileDownload(
                supportedStorages: ['downloads'],
            ),
            new Feature\FileDisplay(
                supportedStorages: ['downloads'],
            ),
        ];
    }

    public function testBlockRender()
    {
        $this->fakeConfig()->with('media.features', $this->getMediaFeatures());
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'downloads')->write(
            path: 'file.pdf',
            content: (string)$http->getFileFactory()->createFile('file.pdf')->getStream()
        );
        
        $block = $app->make(DownloadsFactory::class)->createBlock([
            'files' => [
                [
                    'src' => 'file.pdf',
                    'storage' => 'downloads',
                    'name' => ['en' => 'Filename'],
                ],
            ],
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('block block-downloads', $rendered);
        $this->assertStringContainsString(
            '<a href="http://localhost/media/download/downloads/file.pdf" class="button">Download</a>',
            $rendered
        );
        $this->assertStringContainsString(
            '<a href="http://localhost/media/file/downloads/file.pdf" class="button" target="_blank">View In Browser</a>',
            $rendered
        );
        $this->assertStringContainsString('Filename', $rendered);
    }
    
    public function testBlockRenderWithMailNamespace()
    {
        $this->fakeConfig()->with('media.features', $this->getMediaFeatures());
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $app = $this->bootingApp();
        $fileStorage->storage(name: 'downloads')->write(
            path: 'file.pdf',
            content: (string)$http->getFileFactory()->createFile('file.pdf')->getStream()
        );
        
        $block = $app->make(DownloadsFactory::class)->withViewNamespace('mail')->createBlock([
            'files' => [
                [
                    'src' => 'file.pdf',
                    'storage' => 'downloads',
                    'name' => ['en' => 'Filename'],
                ],
            ],
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('block block-downloads', $rendered);
        $this->assertStringContainsString(
            '<a href="http://localhost/media/download/downloads/file.pdf" class="button">Download</a>',
            $rendered
        );
        $this->assertStringContainsString(
            '<a href="http://localhost/media/file/downloads/file.pdf" class="button" target="_blank">View In Browser</a>',
            $rendered
        );
        $this->assertStringContainsString('Filename', $rendered);
    }
    
    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(DownloadsFactory::class)->createBlock([
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<div class="pt-xs block block-downloads', $block->render());
    }
    
    public function testStoreAction()
    {
        $this->fakeConfig()->with('media.features', $this->getMediaFeatures());
        $fileStorage = $this->fakeFileStorage();
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/store-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => [
                'type' => 'downloads',
                'data' => [
                    'files' => [
                        [
                            'src' => $http->getFileFactory()->createImage('image.jpg', 50, 50),
                            'name' => [
                                'en' => 'Filename',
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
                     ->has(key: 'block.data.files.0.src', value: 'image.jpg')
                     ->has(key: 'block.data.files.0.name.en', value: 'Filename')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('image.jpg', $block->get('data.files.0.src'));
        $this->assertSame('Filename', $block->get('data.files.0.name.en'));
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
            'block' => ['type' => 'downloads'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'data.files');
    }
    
    public function testUpdateAction()
    {
        $this->fakeConfig()->with('media.features', $this->getMediaFeatures());
        $http = $this->fakeHttp();
        $http->request(
            method: 'POST',
            uri: 'block-editor/update-block',
            headers: ['Accept' => 'application/json'],
        )->body([
            'editor' => 'default',
            'block' => json_encode([
                'id' => 1,
                'type' => 'downloads',
                'data' => [
                    'files' => [
                        [
                            'name' => ['en' => 'Name New'],                         
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
            'type' => 'downloads',
            'data' => [
                'files' => [
                    [
                        'src' => 'image.jpg',
                        'name' => ['en' => 'Name'],
                    ],
                ],
            ],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.data.files.0.src', value: 'image.jpg')
                     ->has(key: 'block.data.files.0.name.en', value: 'Name New')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('image.jpg', $block->get('data.files.0.src'));
        $this->assertSame('Name New', $block->get('data.files.0.name.en'));
    }
}