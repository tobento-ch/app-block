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

namespace Tobento\App\Block\Test\Block;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Block\NullBlock;
use Tobento\App\Block\BlockInterface;

class NullBlockTest extends TestCase
{
    public function testImplementsBlockInterface(): void
    {
        $block = new NullBlock();

        $this->assertInstanceOf(BlockInterface::class, $block);
    }

    public function testRenderMethodReturnsEmptyString(): void
    {
        $block = new NullBlock();

        $this->assertSame('', $block->render());
    }
}