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
 
namespace Tobento\App\Block\ResourceResolver;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\ResourceInterface;
use Tobento\App\Block\ResourceResolver\Slugs;
use Tobento\App\Block\ResourceResolverInterface;
use Tobento\App\Block\Test\Factory;

class SlugsTest extends TestCase
{
    public function testThatImplementsResourceResolverInterface()
    {
        $this->assertInstanceof(ResourceResolverInterface::class, new Slugs(Factory::createRouter()));
    }
    
    public function testResolveMethodReturnsNullWithoutMatchedRoute()
    {
        $this->assertNull((new Slugs(Factory::createRouter()))->resolve());
    }
    
    public function testResolveMethodReturnsResourceWithIdAndKey()
    {
        $router = Factory::createRouter(uri: 'foo');
        
        $router->get('foo', function() {
            return 'Foo';
        })->parameter('slug.resourceId', 'ID')->parameter('slug.resourceKey', 'KEY');
        
        $router->dispatch();
        
        $resolver = new Slugs($router);
        
        $this->assertSame('KEY:ID', $resolver->resolve()?->id());
        $this->assertSame('', $resolver->resolve()?->group());
    }
    
    public function testResolveMethodReturnsResourceWithIdOnly()
    {
        $router = Factory::createRouter(uri: 'foo');
        
        $router->get('foo', function() {
            return 'Foo';
        })->parameter('slug.resourceId', 'ID')->parameter('slug.resourceKey', '');
        
        $router->dispatch();
        
        $resolver = new Slugs($router);
        
        $this->assertSame('ID', $resolver->resolve()?->id());
        $this->assertSame('', $resolver->resolve()?->group());
    }
}