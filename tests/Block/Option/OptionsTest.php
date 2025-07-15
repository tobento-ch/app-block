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
 
namespace Tobento\App\Block\Test\Block\Option;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Block\Option\Options;
use Tobento\App\Block\Block\Option\OptionsInterface;

class OptionsTest extends TestCase
{
    public function testThatImplementsOptionsInterface()
    {
        $this->assertInstanceOf(OptionsInterface::class, new Options([]));
    }
    
    public function testToTagAttributesMethod()
    {
        $this->assertSame('', (new Options([]))->toTagAttributes()->render());
        
        $options = new Options([
            'padding' => [
                'top' => 's',
                'bottom' => 'm',
                'left' => 'l',
                'right' => 'xl',
            ],
            'margin' => [
                'top' => 's',
                'bottom' => 'm',
                'left' => 'l',
                'right' => 'xl',
            ],
            'color' => [
                'background' => 'primary',
                'text' => 'secondary',
            ],
            'classes' => [
                'foo', 'bar', '!invalid',
            ],
        ]);
        
        $this->assertSame(
            ' class="pt-s pb-m pl-l pr-xl mt-s mb-m ml-l mr-xl background-primary text-secondary foo bar"',
            $options->toTagAttributes()->render()
        );
        
        $options = new Options([
            'padding' => [
                's',
                ['bottom' => 'm'],
            ],
            [],
        ]);
        
        $this->assertSame('', $options->toTagAttributes()->render());
    }
    
    public function testGetMethod()
    {
        $this->assertSame(null, (new Options([]))->get('foo'));
        $this->assertSame('Foo', (new Options(['foo' => 'Foo']))->get('foo'));
    }
    
    public function testAllMethod()
    {
        $this->assertSame([], (new Options([]))->all());
        $this->assertSame(['foo' => 'Foo'], (new Options(['foo' => 'Foo']))->all());
    }    
}