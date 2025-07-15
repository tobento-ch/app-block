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
use Tobento\App\Block\Resource;
use Tobento\App\Block\ResourceInterface;
use Tobento\App\Block\ResourceResolver\Composite;
use Tobento\App\Block\ResourceResolverInterface;

class CompositeTest extends TestCase
{
    public function testThatImplementsResourceResolverInterface()
    {
        $this->assertInstanceof(ResourceResolverInterface::class, new Composite());
    }
    
    public function testResolveMethodReturnsNullIfNone()
    {
        $this->assertNull((new Composite())->resolve());
    }
    
    public function testResolveMethodReturnsFirstFound()
    {
        $resolver = new Composite(
            new class() implements ResourceResolverInterface {
                public function resolve(): null|ResourceInterface
                {
                    return null;
                }
            },
            new class(new Resource(id: 'foo')) implements ResourceResolverInterface {
                public function __construct(
                    private ResourceInterface $resource
                ) {}
                public function resolve(): null|ResourceInterface
                {
                    return $this->resource;
                }
            },
            new class() implements ResourceResolverInterface {
                public function resolve(): null|ResourceInterface
                {
                    return null;
                }
            }
        );
        
        $this->assertSame('foo', $resolver->resolve()?->id());
    }
}