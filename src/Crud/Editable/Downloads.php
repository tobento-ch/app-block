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
 * Downloads
 */
class Downloads implements EditableBlockInterface
{
    use Traits\NormalizesFileSourceInput;
    
    /**
     * Create a new Downloads instance.
     *
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     * @param array<array-key, string> $allowedFileExtensions
     * @param int $maxNumberOfFiles
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-downloads'],
        protected array $allowedFileExtensions = ['jpg', 'png', 'webp', 'pdf'],
        protected int $maxNumberOfFiles = 50,
    ) {}
    
    /**
     * Returns the title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Downloads');
    }
    
    /**
     * Returns the description.
     *
     * @return string
     */
    public function description(): string
    {
        return trans('Add files to download or to view in browser.');
    }
    
    /**
     * Returns the icon. Any data from unsecure source must be HTML escaped.
     *
     * @return string
     */
    public function icon(): string
    {
        return '<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>';
    }
    
    /**
     * Returns the default block.
     *
     * @return array<string, mixed>
     */
    public function defaultBlock(): array
    {
        return ['type' => 'downloads'];
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
            new Field\Files(name: 'data.files', label: trans('Files'))
                ->group(trans('Files'))
                ->numberOfFiles(max: $this->maxNumberOfFiles)
                ->translatable()
                ->file(function(Field\File $file): void {
                    $file->translatable();
                    $file->fileSource(function(Field\FileSource $fs): void {
                        $fs->allowedExtensions(...$this->allowedFileExtensions);
                        $fs->storage(name: 'downloads');
                        $fs->imageEditor(template: 'default');
                    });
                    $file->storeFilenameTo('name');
                })
                ->fields(
                    new Field\Text('name', trans('Name'))
                        ->validate('string|htmlclean')
                        ->translatable(),
                    new Field\FileSource(name: 'image', label: trans('Preview Image'))
                        ->allowedExtensions('jpg', 'png', 'webp')
                        ->storage(name: 'uploads-public')
                        ->pictureEditor(template: 'default', definitions: $this->pictureDefinitions),
                ),
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
            
            $files = $block['data']['files'] ?? [];
            
            if (!is_array($files)) {
                $files = [];
            }
            
            foreach(array_keys($files) as $key) {
                unset($block['data']['files'][$key]['src']);
                unset($block['data']['files'][$key]['image']);
            }
        }
        
        if ($action->name() === 'store') {
            foreach ($block['data']['files'] ?? [] as $key => $file) {

                // Normalize main file
                $block['data']['files'][$key] = $this->normalizeFileSource($file);

                // Normalize preview image
                if (isset($file['image'])) {
                    $image = $file['image'];

                    if (is_string($image) && $image !== '') {
                        $block['data']['files'][$key]['image'] = [
                            'storage' => 'uploads-public',
                            'path'    => $image,
                        ];
                    }
                }
            }
        }
        
        return $block;
    }
}