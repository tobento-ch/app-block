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
 
namespace Tobento\App\Block\Test;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Resource;
use Tobento\App\Block\ResourceInterface;

class ResourceTest extends TestCase
{
    public function testThatImplementsResourceInterface()
    {
        $this->assertInstanceof(ResourceInterface::class, new Resource(id: 'ID'));
    }
    
    public function testWithIdMethod()
    {
        $resource = new Resource(id: 'ID');
        $resourceNew = $resource->withId(id: 'foo');
        
        $this->assertFalse($resource === $resourceNew);
        $this->assertSame('foo', $resourceNew->id());
    }
    
    public function testIdMethod()
    {
        $this->assertSame('ID', (new Resource(id: 'ID'))->id());
    }
    
    public function testWithGroupMethod()
    {
        $resource = new Resource(id: 'ID', group: 'main');
        $resourceNew = $resource->withGroup(group: 'foo');
        
        $this->assertFalse($resource === $resourceNew);
        $this->assertSame('foo', $resourceNew->group());
    }
    
    public function testGroupMethod()
    {
        $this->assertSame('main', (new Resource(id: 'ID', group: 'main'))->group());
    }
}