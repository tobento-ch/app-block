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

namespace Tobento\App\Block\Migration;

use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;
use Tobento\Service\Repository\Storage\Migration\RepositoryAction;
use Tobento\Service\Repository\Storage\Migration\RepositoryDeleteAction;

/**
 * BlockRepository migration.
 */
class BlockRepository implements MigrationInterface
{
    /**
     * Create a new BlockRepository instance.
     *
     * @param BlockRepositoryInterface $blockRepository
     */
    public function __construct(
        protected BlockRepositoryInterface $blockRepository,
    ) {}
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Block resource.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            RepositoryAction::newOrNull(
                repository: $this->blockRepository,
                description: 'Block resource.',
            ),
        );
    }

    /**
     * Return the actions to be processed on uninstall.
     *
     * @return ActionsInterface
     */
    public function uninstall(): ActionsInterface
    {
        return new Actions(
            RepositoryDeleteAction::newOrNull(
                repository: $this->blockRepository,
                description: 'Block resource',
            ),
        );
    }
}