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
 
namespace Tobento\App\Block\Editable\Option;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\Service\Repository\RepositoryInterface;
use Tobento\Service\Repository\Storage\Column;
use Tobento\Service\Repository\Storage\Column\ColumnInterface;
use Tobento\Service\Repository\Storage\Column\ColumnsInterface;
use Tobento\Service\Repository\Storage\StorageRepository;
use Tobento\Service\Storage\InMemoryStorage;

/**
 * Classes option.
 */
class Classes implements OptionInterface
{
    /**
     * Create a new Classes instance.
     *
     * @param array<string, string> $classes
     * @param string $groupName
     * @param bool $searchableClasses
     */
    public function __construct(
        protected array $classes = [],
        protected string $groupName = 'Classes',
        protected bool $searchableClasses = true,
    ) {}
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        if ($this->searchableClasses === false) {
            return [
                Field\Checkboxes::new('options.classes', $action->trans('Classes'))
                    ->group($action->trans($this->groupName))
                    ->options($this->classes()),
            ];
        }
        
        return [
            Field\Options::new('options.classes', $action->trans('Classes'))
                ->group($action->trans($this->groupName))
                ->repository($this->repository())
                ->toOption(function(object $item): Field\Option {        
                    return new Field\Option(
                        value: (string)$item->get('class'),
                        text: (string)$item->get('title'),
                    );
                })
                ->limit(100)
                ->storeColumn('class')
                ->searchColumns('class', 'title')
                ->placeholder(text: $action->trans('Search for classes')),
        ];
    }

    /**
     * Returns the css classes.
     *
     * @return array<string, string>
     */
    public function classes(): array
    {
        if (!empty($this->classes)) {
            return $this->classes;
        }
        
        return [
            // Padding:
            'pt-xs' => 'padding-top-xs',
            'pt-s' => 'padding-top-s',
            'pt-m' => 'padding-top-m',
            'pt-l' => 'padding-top-l',
            'pt-xl' => 'padding-top-xl',
            'pt-xxl' => 'padding-top-xxl',
            'pb-xs' => 'padding-bottom-xs',
            'pb-s' => 'padding-bottom-s',
            'pb-m' => 'padding-bottom-m',
            'pb-l' => 'padding-bottom-l',
            'pb-xl' => 'padding-bottom-xl',
            'pb-xxl' => 'padding-bottom-xxl',
            'pl-xs' => 'padding-left-xs',
            'pl-s' => 'padding-left-s',
            'pl-m' => 'padding-left-m',
            'pl-l' => 'padding-left-l',
            'pl-xl' => 'padding-left-xl',
            'pl-xxl' => 'padding-left-xxl',
            'pr-xs' => 'padding-right-xs',
            'pr-s' => 'padding-right-s',
            'pr-m' => 'padding-right-m',
            'pr-l' => 'padding-right-l',
            'pr-xl' => 'padding-right-xl',
            'pr-xxl' => 'padding-right-xxl',
            
            // Margin:
            'mt-xs' => 'margin-top-xs',
            'mt-s' => 'margin-top-s',
            'mt-m' => 'margin-top-m',
            'mt-l' => 'margin-top-l',
            'mt-xl' => 'margin-top-xl',
            'mt-xxl' => 'margin-top-xxl',
            'mb-xs' => 'margin-bottom-xs',
            'mb-s' => 'margin-bottom-s',
            'mb-m' => 'margin-bottom-m',
            'mb-l' => 'margin-bottom-l',
            'mb-xl' => 'margin-bottom-xl',
            'mb-xxl' => 'margin-bottom-xxl',
            'ml-xs' => 'margin-left-xs',
            'ml-s' => 'margin-left-s',
            'ml-m' => 'margin-left-m',
            'ml-l' => 'margin-left-l',
            'ml-xl' => 'margin-left-xl',
            'ml-xxl' => 'margin-left-xxl',
            'mr-xs' => 'margin-right-xs',
            'mr-s' => 'margin-right-s',
            'mr-m' => 'margin-right-m',
            'mr-l' => 'margin-right-l',
            'mr-xl' => 'margin-right-xl',
            'mr-xxl' => 'margin-right-xxl',
            
            // Colors
            'background-white' => 'background-color-white',
            'background-black' => 'background-color-black',
            'background-primary' => 'background-color-primary',
            'background-secondary' => 'background-color-secondary',
            'background-success' => 'background-color-success',
            'background-info' => 'background-color-info',
            'background-warning' => 'background-color-warning',
            'background-error' => 'background-color-error',
            'background-highlight' => 'background-color-highlight',
            
            'text-white' => 'text-color-white',
            'text-black' => 'text-color-black',
            'text-primary' => 'text-color-primary',
            'text-secondary' => 'text-color-secondary',
            'text-success' => 'text-color-success',
            'text-info' => 'text-color-info',
            'text-warning' => 'text-color-warning',
            'text-error' => 'text-color-error',
            'text-highlight' => 'text-color-highlight',
        ];
    }
    
    /**
     * Returns the repository.
     *
     * @return RepositoryInterface
     */
    protected function repository(): RepositoryInterface
    {
        $items = [];
        
        foreach($this->classes() as $class => $title) {
            $items[] = ['class' => $class, 'title' => $title];
        }
        
        return new class(
            storage: new InMemoryStorage(['classes' => $items]),
            table: 'classes',
        ) extends StorageRepository {
            protected function configureColumns(): iterable|ColumnsInterface
            {
                return [
                    Column\Text::new('class'),
                    Column\Text::new('title'),
                ];
            }
        };
    }
}