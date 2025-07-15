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

use Symfony\Component\DomCrawler\Crawler;
use Tobento\App\AppInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\Middleware\BlockViewsEditor;
use Tobento\Service\Responser\ResponserInterface;
use Tobento\Service\Routing\RouterInterface;

class BlockViewsEditorTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        $app->boot(App\MigrationBoot::class);
        
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('article', function (ResponserInterface $responser) {
                return $responser->render(
                    view: 'block/test/article',
                    data: [],
                );
            })->middleware([
                BlockViewsEditor::class,
                'editorName' => 'default',
                'editable' => true,
                'resourceId' => 'article',
                'resourceGroup' => 'main',
            ]);
        });
        
        return $app;
    }

    public function testAddNewBlockEditorsAreRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article');
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Add Block');
        
        $this->assertCount(4, $response->crawl()->filter('.blocks'));
        $this->assertCount(4, $response->crawl()->filter('[data-block-editor]'));
        $this->assertCount(4, $response->crawl()->filter('[data-block-action="new"]'));
    }
    
    public function testExistingBlocksAreRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'article');
        
        $app = $this->bootingApp();
        $editors = $app->get(EditorsInterface::class);
        $editor = $editors->get('default');
        $blockRepository = $editor->getBlockRepository();
        
        $blocks = [
            ['type' => 'text', 'position' => 'resource', 'translation' => ['en' => 'Text Resource']],
            ['type' => 'text', 'position' => 'resource.footer', 'translation' => ['en' => 'Text Resource Footer']],
            ['type' => 'text', 'position' => 'header', 'translation' => ['en' => 'Text Header']],
            ['type' => 'text', 'position' => 'aside', 'translation' => ['en' => 'Text Aside']],
        ];
        
        foreach($blocks as $block) {
            $block['editor'] = 'default';
            $block['resource_id'] = 'article';
            $block['resource_group'] = 'main';
            $blockRepository->create($block);
        }
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertBodyContains('Add Block')
            ->assertBodyContains('Text Resource')
            ->assertBodyContains('Text Resource Footer')
            ->assertBodyContains('Text Header')
            ->assertBodyNotContains('Text Aside')
            ->assertNodeExists('[data-editor]');
        
        $this->assertCount(4, $response->crawl()->filter('.blocks'));
        $this->assertCount(4, $response->crawl()->filter('[data-block-editor]'));
        $this->assertCount(7, $response->crawl()->filter('[data-block-action="new"]'));
    }
    
    public function testUneditableBlocksAreRendered()
    {
        $http = $this->fakeHttp();
        $http->request(method: 'GET', uri: 'about');
        
        $app = $this->getApp();
        $app->on(RouterInterface::class, static function(RouterInterface $router): void {
            $router->get('about', function (ResponserInterface $responser) {
                return $responser->render(
                    view: 'block/test/article',
                    data: [],
                );
            })->middleware([
                BlockViewsEditor::class,
                'editorName' => 'default',
                'editable' => false,
                'resourceId' => 'about',
                'resourceGroup' => 'main',
            ]);
        });
        
        $app->booting();
        $editors = $app->get(EditorsInterface::class);
        $editor = $editors->get('default');
        $blockRepository = $editor->getBlockRepository();
        
        $blocks = [
            ['type' => 'text', 'position' => 'resource', 'translation' => ['en' => 'Text Resource']],
        ];
        
        foreach($blocks as $block) {
            $block['editor'] = 'default';
            $block['resource_id'] = 'about';
            $block['resource_group'] = 'main';
            $blockRepository->create($block);
        }
        
        $response = $http->response()
            ->assertStatus(200)
            ->assertBodyNotContains('Add Block')
            ->assertBodyContains('Text Resource')
            ->assertNodeMissing('[data-editor]');
        
        $this->assertCount(4, $response->crawl()->filter('.blocks'));
        $this->assertCount(0, $response->crawl()->filter('[data-block-editor]'));
        $this->assertCount(0, $response->crawl()->filter('[data-block-action="new"]'));
    }
}