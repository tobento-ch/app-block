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
 
namespace Tobento\App\Block\Test\Mail;

use PHPUnit\Framework\TestCase;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Mail\MailBlock;
use Tobento\App\Mail\Block\BlockInterface as MailBlockInterface;
use Tobento\App\Block\Test\Factory;

class MailBlockTest extends TestCase
{
    public function testBlock()
    {
        $block = new class() implements BlockInterface {
            public function render(): string
            {
                return 'html';
            }
        };
        
        $mailBlock = new MailBlock(block: $block);
        
        $this->assertInstanceof(MailBlockInterface::class, $mailBlock);
        $this->assertSame('html', $mailBlock->render(view: Factory::createView()));
    }
}