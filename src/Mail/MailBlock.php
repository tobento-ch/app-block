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
 
namespace Tobento\App\Block\Mail;

use Tobento\App\Block\BlockInterface;
use Tobento\App\Mail\Block\BlockInterface as MailBlockInterface;
use Tobento\Service\View\ViewInterface;

/**
 * Mail block
 */
final class MailBlock implements MailBlockInterface
{
    /**
     * Create a new MailBlock instance.
     *
     * @param BlockInterface $block
     */
    public function __construct(
        private BlockInterface $block,
    ) {}
    
    /**
     * Returns the html of the block. MUST be escaped.
     *
     * @param ViewInterface $view
     * @return string
     */
    public function render(ViewInterface $view): string
    {
        return $this->block->render();
    }
}