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
use Tobento\App\Block\Exception\EditorNotFoundException;

class EditorNotFoundExceptionTest extends TestCase
{
    public function testException()
    {
        $e = new EditorNotFoundException(editor: 'name');
        
        $this->assertInstanceof(BlockException::class, $e);
        $this->assertSame('Editor name not found', $e->getMessage());
        $this->assertSame('name', $e->editor());
    }
}