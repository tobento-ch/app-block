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

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;

/**
 * EditableBlockInterface
 */
interface EditableBlockInterface
{
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string;
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string;
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string;
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array;
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface;

    /**
     * Map the block to the fields.
     *
     * @param array<string, mixed> $block
     * @param ActionInterface $action
     * @return array<string, mixed>
     */
    public function toFields(array $block, ActionInterface $action): array;
}