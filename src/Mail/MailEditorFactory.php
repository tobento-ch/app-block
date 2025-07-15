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

use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\Editor\BlockFactory;
use Tobento\App\Block\Editor\EditorFactory;

/**
 * Mail specific editor factory
 */
class MailEditorFactory extends EditorFactory
{
    /**
     * Returns the created block factory.
     *
     * @return BlockFactoryInterface
     */
    protected function createBlockFactory(): BlockFactoryInterface
    {
        return new BlockFactory(container: $this->container, viewNamespace: 'mail');
    }
}