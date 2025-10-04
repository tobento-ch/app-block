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
 
namespace Tobento\App\Block\Test\Editable\Option;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Editable;
use Tobento\App\Block\Editable\Option;
use Tobento\App\Block\Editable\Option\OptionInterface;
use Tobento\App\Block\Editable\Option\Options;
use Tobento\App\Block\Editable\Option\OptionsInterface;
use Tobento\App\Crud\Action;

class OptionsTest extends TestCase
{
    public function testThatImplementsOptionsInterface()
    {
        $this->assertInstanceOf(OptionsInterface::class, new Options([]));
    }
    
    public function testConfigureFieldsMethod()
    {
        $options = new Options([
            'padding' => new Option\Padding(),
            'margin' => new Option\Margin(),
        ]);
        
        $this->assertSame(
            8,
            count($options->configureFields(
                action: new Action\Create(),
                block: new Editable\Text($options),
            ))
        );
    }
    
    public function testOnlyMethod()
    {
        $options = new Options([
            'padding' => new Option\Padding(),
            'margin' => new Option\Margin(),
            'color' => new Option\Color(),
        ]);
        
        $optionsNew = $options->only(['padding']);
        $this->assertFalse($options === $optionsNew);
        $this->assertSame(['padding'], array_keys($optionsNew->getOptions()));
        $this->assertInstanceof(OptionInterface::class, $optionsNew->getOptions()['padding']);
        
        $this->assertSame([], array_keys($options->only([])->getOptions()));
        $this->assertSame(['padding', 'color'], array_keys($options->only(['padding', 'color', 'foo'])->getOptions()));
        $this->assertSame([], array_keys((new Options([]))->only(['padding'])->getOptions()));
    }
    
    public function testExceptMethod()
    {
        $options = new Options([
            'padding' => new Option\Padding(),
            'margin' => new Option\Margin(),
            'color' => new Option\Color(),
        ]);
        
        $optionsNew = $options->except(['padding', 'color']);
        $this->assertFalse($options === $optionsNew);
        $this->assertSame(['margin'], array_keys($optionsNew->getOptions()));
        $this->assertInstanceof(OptionInterface::class, $optionsNew->getOptions()['margin']);
        
        $this->assertSame(['padding', 'margin', 'color'], array_keys($options->except([])->getOptions()));
        $this->assertSame(['margin', 'color'], array_keys($options->except(['padding', 'foo'])->getOptions()));
        $this->assertSame([], array_keys((new Options([]))->except(['padding'])->getOptions()));
    }
    
    public function testReorderMethod()
    {
        $options = new Options([
            'padding' => new Option\Padding(),
            'margin' => new Option\Margin(),
            'color' => new Option\Color(),
        ]);
        
        $optionsNew = $options->reorder('color', 'foo', 'margin');
        $this->assertFalse($options === $optionsNew);
        $this->assertSame(['color', 'margin', 'padding'], array_keys($optionsNew->getOptions()));
        $this->assertSame([], array_keys((new Options([]))->reorder('padding')->getOptions()));
    }
    
    public function testWithOptionMethod()
    {
        $options = new Options([
            'padding' => new Option\Padding(),
            'margin' => new Option\Margin(),
        ]);
        
        $option = new Option\Color();
        $optionsNew = $options->withOption(name: 'color', option: $option);
        $this->assertFalse($options === $optionsNew);
        $this->assertTrue($option === $optionsNew->getOptions()['color']);
    }
}