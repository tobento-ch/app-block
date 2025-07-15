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

namespace Tobento\App\Block\Exception;

use RuntimeException;
use Throwable;

/**
 * BlockCreateException
 */
class BlockCreateException extends BlockException
{
    /**
     * Create a new BlockCreateException.
     *
     * @param array $block
     * @param string $message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected array $block,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null,
    ) {
        if ($message === '') {
            $type = $block['type'] ?? '';
            $type = is_string($type) ? $type : '';
            $message = sprintf('Unable to create block [%s]: %s', $type, (string)$previous?->getMessage());
        }
        
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the block.
     *
     * @return array
     */
    public function block(): array
    {
        return $this->block;
    }
}