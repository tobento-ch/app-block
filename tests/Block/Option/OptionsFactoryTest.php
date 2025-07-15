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
use Tobento\App\Block\Block\Option\OptionsFactory;
use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Block\Option\OptionsInterface;

class OptionsFactoryTest extends TestCase
{
    public function testThatImplementsOptionsFactoryInterface()
    {
        $this->assertInstanceOf(OptionsFactoryInterface::class, new OptionsFactory());
    }
    
    public function testCreateOptionsMethod()
    {
        $this->assertInstanceOf(OptionsInterface::class, (new OptionsFactory())->createOptions([]));
    }
}