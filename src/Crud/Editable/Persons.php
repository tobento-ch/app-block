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
 
namespace Tobento\App\Block\Editable;

use Tobento\App\Block\Editable\Option\OptionsInterface;
use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

/**
 * Persons
 */
class Persons implements EditableBlockInterface
{
    use Traits\NormalizesFileSourceInput;
    
    /**
     * Create a new Persons instance.
     *
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-persons'],
    ) {}
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Persons');
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Add persons with their contact information.');
    }
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>';
    }
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => 'persons'];
    }
    
    /**
     * Returns the configured fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\Items('data.persons')
                // you may group the fields
                ->group('Persons') // set before defining fields!

                // define the fields per item:
                ->fields(
                    new Field\Text(name: 'name', label: trans('Name'))
                        ->type('text')
                        ->validate('string|htmlclean|maxLen:100'),
                    new Field\Text(name: 'position', label: trans('Position'))
                        ->type('text')
                        ->validate('string|htmlclean|maxLen:100'),
                    new Field\Text(name: 'email', label: trans('E-Mail'))
                        ->type('email')
                        ->validate('email'),
                    new Field\Text(name: 'tel', label: trans('Telephone'))
                        ->type('tel')
                        ->validate('string|htmlclean|minLen:6|maxLen:20'),
                    new Field\FileSource(name: 'image', label: trans('Image'))
                        ->storage(name: 'uploads-public')
                        ->allowedExtensions('jpg', 'png', 'webp')
                        ->pictureEditor(template: 'default', definitions: $this->pictureDefinitions),
                )
                ->validate('maxItems:50')
                ->defaultItems(num: 1)
                ->addText(trans('Add new person'))
                ->withoutLabel(),
            ...$this->options->configureFields($action, $this),
        ];
    }
    
    /**
     * Map the block to the fields.
     *
     * @param array<string, mixed> $block
     * @param ActionInterface $action
     * @return array<string, mixed>
     */
    public function toFields(array $block, ActionInterface $action): array
    {
        if ($action->name() === 'update') {
            $persons = $block['data']['persons'] ?? [];
            
            if (!is_array($persons)) {
                $persons = [];
            }
            
            foreach(array_keys($persons) as $key) {
                unset($block['data']['persons'][$key]['image']);
            }
        }
        
        // STORE: convert stored format to FileSource input format
        if ($action->name() === 'store') {

            $persons = $block['data']['persons'] ?? [];

            if (!is_array($persons)) {
                return $block;
            }

            foreach ($persons as $key => $person) {

                if (!is_array($person)) {
                    continue;
                }

                // Normalize image into FileSource format
                if (isset($person['image'])) {
                    $image = $person['image'];

                    if (is_string($image) && $image !== '') {
                        $block['data']['persons'][$key]['image'] = [
                            'storage' => 'uploads-public',
                            'path'    => $image,
                        ];
                    }
                }
            }

            return $block;
        }
        
        return $block;
    }
}