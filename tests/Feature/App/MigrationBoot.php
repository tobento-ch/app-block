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

use Tobento\App\Boot;
use Tobento\App\Migration\Boot\Migration;

class MigrationBoot extends Boot
{
    public const BOOT = [
        Migration::class,
    ];

    public function boot(
        Migration $migration,
    ): void {
        $migration->install(\Tobento\App\Block\Test\Feature\App\Migration::class);
    }
}