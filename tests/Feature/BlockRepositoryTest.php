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
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\Resource;

class BlockRepositoryTest extends \Tobento\App\Testing\TestCase
{
    use \Tobento\App\Testing\Database\RefreshDatabases;
    
    public function createApp(): AppInterface
    {
        $app = $this->createTmpApp(rootDir: __DIR__.'/../..');
        $app->boot(\Tobento\App\Block\Boot\Block::class);
        return $app;
    }

    public function testFindAllByResourceMethodWithIdOnly()
    {
        $app = $this->bootingApp();
        $repo = $app->get(BlockRepositoryInterface::class);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => '']);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => '']);
        $repo->create(['resource_id' => 'articles:2', 'resource_group' => '']);
        
        $this->assertSame(2, $repo->findAllByResource(new Resource(id: 'articles:1'))->count());
    }
    
    public function testFindAllByResourceMethodWithIdAndGroup()
    {
        $app = $this->bootingApp();
        $repo = $app->get(BlockRepositoryInterface::class);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => 'main']);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => '']);
        $repo->create(['resource_id' => 'articles:2', 'resource_group' => '']);
        
        $this->assertSame(1, $repo->findAllByResource(new Resource(id: 'articles:1', group: 'main'))->count());
    }
    
    public function testFindAllByResourceMethodIsSorted()
    {
        $app = $this->bootingApp();
        $repo = $app->get(BlockRepositoryInterface::class);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => '', 'sortorder' => 2]);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => '', 'sortorder' => 3]);
        $repo->create(['resource_id' => 'articles:1', 'resource_group' => '', 'sortorder' => 1]);
        
        $titles = [];
        
        foreach($repo->findAllByResource(new Resource(id: 'articles:1')) as $item) {
            $titles[] = $item->get('sortorder');
        }
        
        $this->assertSame([1, 2, 3], $titles);
    }
}