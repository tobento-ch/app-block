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
 
namespace Tobento\App\Block\Test\Exception;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\Exception\BlockException;

class BlockExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new BlockException(message: 'Message');
        
        $this->assertInstanceof(\RuntimeException::class, $e);
        $this->assertSame('Message', $e->getMessage());
    }
}