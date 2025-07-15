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
 
namespace Tobento\App\Block\Test\Factory;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Block\Option\Options;
use Tobento\App\Block\Factory\Helper;
use Tobento\App\Block\Test\Factory;

class HelperTest extends TestCase
{
    public function testResolveViewNameMethod()
    {
        $this->assertSame(
            'block/text',
            Helper::resolveViewName(
                view: Factory::createView(),
                name: 'block/text',
            )
        );
    }
    
    public function testResolveViewNameMethodWithNamespace()
    {
        $this->assertSame(
            'block/mail/text',
            Helper::resolveViewName(
                view: Factory::createView(),
                name: 'block/text',
                namespace: 'mail',
            )
        );
        
        $this->assertSame(
            'block/text',
            Helper::resolveViewName(
                view: Factory::createView(),
                name: 'block/text',
                namespace: '',
            )
        );
        
        // namespace not added as view name not starting with 'block'!
        $this->assertSame(
            'custom/text',
            Helper::resolveViewName(
                view: Factory::createView(),
                name: 'custom/text',
                namespace: 'mail',
            )
        );
    }
    
    public function testResolveViewNameMethodWithLayoutOptionNotAddedAsViewNotExists()
    {
        // Not added as view not exists!
        $this->assertSame(
            'block/text',
            Helper::resolveViewName(
                view: Factory::createView(),
                name: 'block/text',
                options: new Options(['layout' => 'foo']),
            )
        );
        
        $view = \Mockery::mock(Factory::createView())->shouldReceive('exists')->andReturn(true)->mock();
        
        $this->assertSame(
            'block/text-foo',
            Helper::resolveViewName(
                view: $view,
                name: 'block/text',
                options: new Options(['layout' => 'foo']),
            )
        );
    }
    
    public function testResolveViewNameMethodWithNamespaceAndLayoutOption()
    {
        $view = \Mockery::mock(Factory::createView())->shouldReceive('exists')->andReturn(true)->mock();
        
        $this->assertSame(
            'block/mail/text-foo',
            Helper::resolveViewName(
                view: $view,
                name: 'block/text',
                namespace: 'mail',
                options: new Options(['layout' => 'foo']),
            )
        );
    }
}