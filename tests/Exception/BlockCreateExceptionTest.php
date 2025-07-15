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
use Tobento\App\Block\Exception\BlockCreateException;

class BlockCreateExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new BlockCreateException(block: ['type' => 'hero'], message: 'Custom Message');
        
        $this->assertInstanceof(BlockException::class, $e);
        $this->assertSame(['type' => 'hero'], $e->block());
        $this->assertSame('Custom Message', $e->getMessage());
    }
    
    public function testDefaultMessageIfNoneIsSet()
    {
        $e = new BlockCreateException(
            block: ['type' => 'hero'],
            previous: new \Exception('Message'),
        );
        $this->assertSame('Unable to create block [hero]: Message', $e->getMessage());
    }
}