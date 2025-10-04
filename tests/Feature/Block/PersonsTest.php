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
use Tobento\App\Block\Factory\Persons as PersonsFactory;
use Tobento\App\Testing\Http\AssertableJson;

class PersonsTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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
        
        $block = $app->make(PersonsFactory::class)->createBlock([
            'persons' => [
                ['name' => 'Tom', 'position' => 'CEO', 'email' => 'tom@example.com', 'tel' => '123-4567-8901'],
            ],
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('<div class="block block-persons', $rendered);
        $this->assertStringContainsString('Tom', $rendered);
        $this->assertStringContainsString('CEO', $rendered);
        $this->assertStringContainsString('<a href="mailto:tom@example.com">tom@example.com</a>', $rendered);
        $this->assertStringContainsString('<a href="tel:123-4567-8901">123-4567-8901</a>', $rendered);
    }
    
    public function testBlockRenderWithMailNamespace()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(PersonsFactory::class)->withViewNamespace('mail')->createBlock([
            'persons' => [
                ['name' => 'Tom', 'position' => 'CEO', 'email' => 'tom@example.com', 'tel' => '123-4567-8901'],
            ],
        ]);
        
        $rendered = $block->render();
        $this->assertStringContainsString('<div class="block block-persons', $rendered);
        $this->assertStringContainsString('Tom', $rendered);
        $this->assertStringContainsString('CEO', $rendered);
        $this->assertStringContainsString('<a href="mailto:tom@example.com">tom@example.com</a>', $rendered);
        $this->assertStringContainsString('<a href="tel:123-4567-8901">123-4567-8901</a>', $rendered);
    }
    
    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();
        
        $block = $app->make(PersonsFactory::class)->createBlock([
            'html' => '<p>lorem</p>',
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);
        
        $this->assertStringContainsString('<div class="pt-xs block block-persons', $block->render());
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
                'type' => 'persons',
                'data' => [
                    'persons' => [
                        1 => ['name' => 'Tom', 'position' => 'CEO', 'email' => 'tom@example.com', 'tel' => '123-4567-8901'],
                        2 => ['name' => 'Tim', 'position' => 'CEO', 'email' => 'tim@example.com', 'tel' => '123-4567-8902'],
                    ],
                ],
            ],
        ]);
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'html')
                     ->has(key: 'block.data.persons.1.name', value: 'Tom')
                     ->has(key: 'block.data.persons.1.position', value: 'CEO')
                     ->has(key: 'block.data.persons.1.email', value: 'tom@example.com')
                     ->has(key: 'block.data.persons.1.tel', value: '123-4567-8901')
                     ->has(key: 'block.data.persons.2.name', value: 'Tim')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('Tom', $block->get('data.persons.1.name'));
        $this->assertSame('CEO', $block->get('data.persons.1.position'));
        $this->assertSame('tom@example.com', $block->get('data.persons.1.email'));
        $this->assertSame('123-4567-8901', $block->get('data.persons.1.tel'));
        $this->assertSame('Tim', $block->get('data.persons.2.name'));
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
            'block' => ['type' => 'persons'],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists(field: 'data.persons');
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
                'type' => 'persons',
                'data' => [
                    'persons' => [
                        1 => ['name' => 'Tim', 'position' => 'CEO', 'email' => 'tim@example.com', 'tel' => '123-4567-8902'],
                    ],
                ],
            ]),
        ]);
        
        $app = $this->bootingApp();
        
        // create block which is done by AJAX:
        $editor = $app->get(EditorsInterface::class)->get('default');
        $editor->getBlockRepository()->create([
            'editor' => 'default',
            'type' => 'persons',
            'data' => [
                'persons' => [
                    1 => ['name' => 'Tom', 'position' => 'CEO', 'email' => 'tom@example.com', 'tel' => '123-4567-8901']
                ]
            ],
        ]);
        
        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has(key: 'status', value: 200)
                     ->has(key: 'block.id', value: 1)
                     ->has(key: 'block.data.persons.1.name', value: 'Tim')
                     ->has(key: 'block.data.persons.1.position', value: 'CEO')
                     ->has(key: 'block.data.persons.1.email', value: 'tim@example.com')
                     ->has(key: 'block.data.persons.1.tel', value: '123-4567-8902')
            );
        
        $block = $this->getCrudRepository()->findById(1);
        $this->assertSame('Tim', $block->get('data.persons.1.name'));
        $this->assertSame('CEO', $block->get('data.persons.1.position'));
        $this->assertSame('tim@example.com', $block->get('data.persons.1.email'));
        $this->assertSame('123-4567-8902', $block->get('data.persons.1.tel'));
    }
}