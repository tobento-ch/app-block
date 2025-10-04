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

use Tobento\App\Block\Crud\Field\BlockEditor;
use Tobento\App\Block\Crud\Field\BlockResourceEditor;
use Tobento\App\Crud\AbstractCrudController;
use Tobento\App\Crud\Button;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Action\ActionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Action;
use Tobento\App\Crud\Entity\EntityInterface;
use Tobento\App\Crud\Filter\FiltersInterface;
use Tobento\App\Crud\Filter\FilterInterface;
use Tobento\App\Crud\Filter;

class ArticleController extends AbstractCrudController
{
    public const RESOURCE_NAME = 'articles';
    
    public function __construct(
        ArticleRepository $repository
    ) {
        $this->repository = $repository;
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\PrimaryId('id'),
            new Field\Text('title'),
            new Field\Slug('slug')->fromField('title'),
            new BlockEditor('blocks'),
            new BlockResourceEditor('resource_blocks')
                ->editable(true)
                ->storable(true)
                ->blockPositions('resource.header', 'resource', 'footer')
                ->resourceGroup('main'),
        ];
    }
    
    /**
     * Returns the configured actions.
     *
     * @return iterable<ActionInterface>|ActionsInterface
     */
    protected function configureActions(): iterable|ActionsInterface
    {
        return [
            new Action\Show(),
            new Action\ShowJson(),
            //new Action\BulkDelete(),
            //new Action\BulkEdit('bulk-title', 'Edit Title')->field('title'),
            new Action\Index('Articles'),
            new Action\Create('New Article'),
            new Action\Store(),
            new Action\Edit('Edit Article'),
            new Action\Update(),
            new Action\Delete(),
        ];
    }
    
    /**
     * Returns the configured filters.
     *
     * @param ActionInterface $action
     * @return iterable<FilterInterface>|FiltersInterface
     */
    protected function configureFilters(ActionInterface $action): iterable|FiltersInterface
    {
        return [];
    }
}