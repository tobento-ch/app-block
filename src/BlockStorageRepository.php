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
 
namespace Tobento\App\Block;

use Tobento\Service\Repository\RepositoryReadException;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\StorageRepository;

/**
 * BlockStorageRepository
 */
class BlockStorageRepository extends StorageRepository implements BlockRepositoryInterface
{
    /**
     * Returns the configured columns.
     *
     * @return iterable<ColumnInterface>|ColumnsInterface
     */
    protected function configureColumns(): iterable|ColumnsInterface
    {
        return [
            new Column\Id(),
            new Column\Text('type'),
            new Column\Text('editor')->type(length: 100, nullable: false),
            new Column\Text('owner')->type(length: 100),
            new Column\Text('resource_id'),
            new Column\Text('resource_group'),
            new Column\Text('status')->type(length: 100),
            new Column\Text('locale')->type(length: 5),
            new Column\Text('position')->type(length: 100),
            new Column\Integer(name: 'sortorder', type: 'int')->type(unsigned: true, nullable: false),
            new Column\Json('options'),
            new Column\Json('data'),
            new Column\Text(name: 'content', type: 'text'),
            new Column\Translatable(name: 'translation', subtype: 'string'),
            new Column\Translatable(name: 'translations', subtype: 'array'),
            new Column\Datetime(name: 'created_at', type: 'timestamp')->autoCreate(),
        ];
    }

    /**
     * Create entity from array.
     *
     * @param array $entity
     * @return BlockEntityInterface
     */
    public function createEntity(array $entity): BlockEntityInterface
    {
        return $this->entityFactory()->createEntityFromArray($entity);
    }
    
    /**
     * Returns the found entities using by the given resource.
     *
     * @param ResourceInterface $resource
     * @return iterable<object>
     */
    public function findAllByResource(ResourceInterface $resource): iterable
    {
        $where = [];

        if ($resource->id() === '') {
            $where['resource_id'] = ['=' => $resource->id()];
        } else {
            $where['resource_id'] = ['=' => $resource->id(), 'or =' => ''];
        }
        
        $where['resource_group'] = ['=' => $resource->group()];
        
        return $this->findAll(
            where: $where,
            orderBy: [
                'sortorder' => 'ASC',
            ],
            limit: 2000,
        );
    }
}