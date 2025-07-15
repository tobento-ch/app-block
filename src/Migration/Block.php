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

use Tobento\Service\Dir\DirsInterface;
use Tobento\Service\Migration\Action\DirCopy;
use Tobento\Service\Migration\Action\DirDelete;
use Tobento\Service\Migration\Action\FilesCopy;
use Tobento\Service\Migration\Action\FilesDelete;
use Tobento\Service\Migration\Actions;
use Tobento\Service\Migration\ActionsInterface;
use Tobento\Service\Migration\MigrationInterface;

/**
 * Block migration.
 */
class Block implements MigrationInterface
{
    protected array $configFiles;
    
    protected array $transFiles;
    
    protected array $viewFiles;
    
    /**
     * Create a new Task instance.
     *
     * @param DirsInterface $dirs
     */
    public function __construct(
        protected DirsInterface $dirs,
    ) {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        $this->configFiles = [
            $this->dirs->get('config') => [
                $resources.'config/block.php',
            ],
        ];
        
        $this->transFiles = [
            $this->dirs->get('trans').'en/' => [
                $resources.'trans/en/en-block.json',
            ],
            $this->dirs->get('trans').'de/' => [
                $resources.'trans/de/de-block.json',
            ],
        ];
        
        $this->viewFiles = [
            $this->dirs->get('views').'picture-definitions/' => [
                $resources.'views/picture-definitions/block-downloads.json',
                $resources.'views/picture-definitions/block-hero.json',
                $resources.'views/picture-definitions/block-image-gallery-large.json',
                $resources.'views/picture-definitions/block-image-gallery.json',
                $resources.'views/picture-definitions/block-image.json',
                $resources.'views/picture-definitions/block-persons.json',
            ],
        ];
    }
    
    /**
     * Return a description of the migration.
     *
     * @return string
     */
    public function description(): string
    {
        return 'Block config file and resources.';
    }
        
    /**
     * Return the actions to be processed on install.
     *
     * @return ActionsInterface
     */
    public function install(): ActionsInterface
    {
        $resources = realpath(__DIR__.'/../../').'/resources/';
        
        return new Actions(
            new FilesCopy(
                files: $this->configFiles,
                type: 'config',
                description: 'Block config file.',
            ),
            new FilesCopy(
                files: $this->transFiles,
                type: 'trans',
                description: 'Translation files.',
            ),
            new FilesCopy(
                files: $this->viewFiles,
                type: 'views',
                description: 'Block view files.',
            ),
            new DirCopy(
                dir: $resources.'views/block/',
                destDir: $this->dirs->get('views').'block/',
                name: 'Block views',
                type: 'views',
                description: 'Block views.',
            ),
            new DirCopy(
                dir: $resources.'assets/block/',
                destDir: $this->dirs->get('public').'assets/block/',
                name: 'Block assets',
                type: 'assets',
                description: 'Block assets.',
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
            new FilesDelete(
                files: $this->configFiles,
                type: 'config',
                description: 'Block config file.',
            ),
            new FilesDelete(
                files: $this->transFiles,
                type: 'trans',
                description: 'Translation files.',
            ),
            new FilesDelete(
                files: $this->viewFiles,
                type: 'views',
                description: 'Block view files.',
            ),
            new DirDelete(
                dir: $this->dirs->get('views').'block/',
                name: 'Block views',
                type: 'views',
                description: 'Block views.',
            ),
            new DirDelete(
                dir: $this->dirs->get('public').'assets/block/',
                name: 'Block assets',
                type: 'assets',
                description: 'Block assets.',
            ),
        );
    }
}