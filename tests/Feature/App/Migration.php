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

namespace Tobento\App\Block\Test\Feature\App;

use Tobento\Service\Dir\DirsInterface;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;

class Migration implements MigrationInterface
{
    public function __construct(
        protected DirsInterface $dirs,
    ) { }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Migration for feature tests';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        return new Actions(
            new DirCopy(
                dir: __DIR__.'/views/',
                destDir: $this->dirs->get('views').'block/test/',
                name: 'Block test views',
                type: 'views',
                description: 'Block test views.',
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
            new DirDelete(
                dir: $this->dirs->get('views').'block/test/',
                name: 'Block test views',
                type: 'views',
                description: 'Block test views.',
            ),
        );
    }
}