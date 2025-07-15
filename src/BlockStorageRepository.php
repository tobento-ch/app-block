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
            Column\Id::new(),
            Column\Text::new('type'),
            Column\Text::new('editor')->type(length: 100, nullable: false),
            Column\Text::new('resource_id'),
            Column\Text::new('resource_group'),
            Column\Text::new('status')->type(length: 100),
            Column\Text::new('locale')->type(length: 5),
            Column\Text::new('position')->type(length: 100),
            Column\Integer::new('sortorder', type: 'int')->type(unsigned: true, nullable: false),
            Column\Json::new('options'),
            Column\Json::new('data'),
            Column\Text::new('content', type: 'text'),
            Column\Translatable::new('translation', subtype: 'string'),
            Column\Translatable::new('translations', subtype: 'array'),
            Column\Datetime::new(name: 'created_at', type: 'timestamp')->autoCreate(),
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