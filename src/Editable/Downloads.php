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
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

/**
 * Downloads Editable Block
 */
class Downloads extends AbstractFields
{
    /**
     * @param OptionsInterface $options
     * @param array<array-key, string> $pictureDefinitions
     * @param array<array-key, string> $allowedFileExtensions
     * @param int $maxNumberOfFiles
     */
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-download'],
        protected array $allowedFileExtensions = ['jpg', 'png', 'webp', 'pdf'],
        protected int $maxNumberOfFiles = 50,
    ) {}
    
    /**
     * Returns the block type used for registration.
     *
     * @return string
     */
    public function type(): string
    {
        return 'downloads';
    }
    
    /**
     * Returns the configured block fields.
     *
     * @param ActionInterface $action
     * @return iterable<FieldInterface>|FieldsInterface
     */
    protected function configureBlockFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Files(name: 'data.files', label: trans('Files'))
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
                new Field\Text(name: 'name', label: trans('Name'))
                    ->validate('string|htmlclean|maxLen:200')
                    ->translatable(),
                new Field\FileSource(name: 'image', label: trans('Preview Image'))
                    ->allowedExtensions('jpg', 'png', 'webp')
                    ->storage(name: 'uploads-public')
                    ->pictureEditor(template: 'default', definitions: $this->pictureDefinitions),
            );
        
        yield new Field\Checkboxes(name: 'data.display', label: trans('Display'))
            ->options([
                'image' => trans('Preview Image'),
                'name' => trans('Name'),
                'filename' => trans('Filename'),
                'format' => trans('Format'),
                'size' => trans('Size'),
                'download' => trans('Download Button'),
                'view' => trans('View Button'),
            ])
            ->selected(
                value: ['image', 'name', 'format', 'size', 'download', 'view'],
                action: 'create|edit',
            )
            ->optionalText('');
    }

    /**
     * Returns the block title.
     *
     * @return string
     */
    public function title(): string
    {
        return trans('Downloads');
    }

    /**
     * Returns the block description.
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
}