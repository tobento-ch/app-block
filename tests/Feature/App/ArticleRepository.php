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

use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\StorageRepository;

class ArticleRepository extends StorageRepository
{
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            new Column\Id(),
            new Column\Text(name: 'title'),
            new Column\Text(name: 'slug'),
            new Column\Json(name: 'blocks'),
            new Column\Json(name: 'resource_blocks'),
        ];
    }
}