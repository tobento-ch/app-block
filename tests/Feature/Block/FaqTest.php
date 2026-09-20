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
use Tobento\App\Block\Factory\Faq as FaqFactory;
use Tobento\App\Testing\Http\AssertableJson;

class FaqTest extends \Tobento\App\Crud\Testing\AbstractCrudTestCase
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

        $block = $app->make(FaqFactory::class)->createBlock([
            'items' => [
                [
                    'question' => 'What is Tobento?',
                    'answer' => '<p>A PHP framework.</p>',
                    'open' => '1',
                ],
            ],
        ]);

        $rendered = $block->render();

        $this->assertStringContainsString('<div class="block block-faq', $rendered);
        $this->assertStringContainsString('What is Tobento?', $rendered);
        $this->assertStringContainsString('<p>A PHP framework.</p>', $rendered);
    }

    public function testBlockRenderWithMailNamespace()
    {
        $app = $this->bootingApp();

        $block = $app->make(FaqFactory::class)->withViewNamespace('mail')->createBlock([
            'items' => [
                [
                    'question' => 'What is Tobento?',
                    'answer' => '<p>A PHP framework.</p>',
                    'open' => '1',
                ],
            ],
        ]);

        $rendered = $block->render();

        $this->assertStringContainsString('<div class="block block-faq', $rendered);
        $this->assertStringContainsString('What is Tobento?', $rendered);
        $this->assertStringContainsString('<p>A PHP framework.</p>', $rendered);
    }

    public function testBlockRenderWithOptions()
    {
        $app = $this->bootingApp();

        $block = $app->make(FaqFactory::class)->createBlock([
            'options' => [
                'padding' => [
                    'top' => 'xs',
                ],
            ],
        ]);

        $this->assertStringContainsString('<div class="pt-xs block block-faq', $block->render());
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
                'type' => 'faq',
                'data' => [
                    'items' => [
                        1 => [
                            'question' =>['en' => 'Q1'],
                            'answer' => ['en' => '<p>A1</p>'],
                            'open' => '1',
                        ],
                        2 => [
                            'question' => ['en' => 'Q2'],
                            'answer' => ['en' => '<p>A2</p>'],
                            'open' => '0',
                        ],
                    ],
                ],
            ],
        ]);

        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('status', 200)
                     ->has('html')
                     ->has('block.data.items.1.question', 'Q1')
                     ->has('block.data.items.1.answer', '<p>A1</p>')
                     ->has('block.data.items.1.open', '1')
                     ->has('block.data.items.2.question', 'Q2')
            );

        $block = $this->getCrudRepository()->findById(1);

        $this->assertSame(['en' => 'Q1'], $block->get('data.items.1.question'));
        $this->assertSame(['en' => '<p>A1</p>'], $block->get('data.items.1.answer'));
        $this->assertSame('1', $block->get('data.items.1.open'));
        $this->assertSame(['en' => 'Q2'], $block->get('data.items.2.question'));
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
            'block' => ['type' => 'faq'],
        ]);

        $http->response()
            ->assertStatus(200)
            ->assertCrudFormFieldExists('data.items');
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
                'type' => 'faq',
                'data' => [
                    'items' => [
                        1 => [
                            'question' => ['en' => 'Updated Q'],
                            'answer' => ['en' => '<p>Updated A</p>'],
                            'open' => '0',
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
            'type'   => 'faq',
            'data'   => [
                'items' => [
                    1 => [
                        'question' => ['en' => 'Old Q'],
                        'answer' => ['en' => '<p>Old A</p>'],
                        'open' => '1',
                    ],
                ],
            ],
        ]);

        $http->response()
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('status', 200)
                     ->has('block.id', 1)
                     ->has('block.data.items.1.question', 'Updated Q')
                     ->has('block.data.items.1.answer', '<p>Updated A</p>')
                     ->has('block.data.items.1.open', '0')
            );

        $block = $this->getCrudRepository()->findById(1);

        $this->assertSame(['en' => 'Updated Q'], $block->get('data.items.1.question'));
        $this->assertSame(['en' => '<p>Updated A</p>'], $block->get('data.items.1.answer'));
        $this->assertSame('0', $block->get('data.items.1.open'));
    }
}