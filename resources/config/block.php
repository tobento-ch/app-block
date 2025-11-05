<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

use Tobento\App\Block\BlockEntityFactory;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\BlockStorageRepository;
use Tobento\App\Block\Editable;
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Mail\MailEditorFactory;
use Tobento\App\Block\Routing\BlockEditorRoutes;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Language\AreaLanguagesInterface;

return [

    /*
    |--------------------------------------------------------------------------
    | Block Editor Routes
    |--------------------------------------------------------------------------
    |
    | If you render uneditable blocks only, you may set null.
    |
    */
    
    'routes' => BlockEditorRoutes::class,
    
    /*
    |--------------------------------------------------------------------------
    | Editors
    |--------------------------------------------------------------------------
    |
    | Configure any block editors needed for your application.
    |
    | See: https://github.com/tobento-ch/app-block#available-editors
    | See: https://github.com/tobento-ch/app-block#creating-custom-editor
    |
    */
    
    'editors' => [
        'default' => static function (
            EditorFactory $factory,
            AreaLanguagesInterface $areaLanguages,
            EditableOptionsInterface $editableOptions,
        ): EditorInterface {
            
            if ($areaLanguages->has('frontend')) {
                $factory = $factory->withLanguages($areaLanguages->get('frontend'));
            }
            
            $factory->addEditableBlocks([
                'downloads' => Editable\Downloads::class,
                'hero' => Editable\Hero::class,
                'image' => Editable\Image::class,
                'image-gallery' => Editable\ImageGallery::class,
                'persons' => Editable\Persons::class,
                'text' => Editable\Text::class,
            ]);
            
            $factory->addBlockFactories([
                'downloads' => [Factory\Downloads::class, 'generateImagesInBackground' => false],
                'hero' => [Factory\Hero::class, 'generateImagesInBackground' => false],
                'image' => [Factory\Image::class, 'generateImagesInBackground' => false],
                'image-gallery' => [Factory\ImageGallery::class, 'generateImagesInBackground' => false],
                'persons' => [Factory\Persons::class, 'generateImagesInBackground' => false],
                'text' => Factory\Text::class,
            ]);
            
            return $factory->createEditor(name: 'default');
        },
        
        'mail' => static function (
            MailEditorFactory $factory,
            AreaLanguagesInterface $areaLanguages
        ): EditorInterface {
            
            if ($areaLanguages->has('frontend')) {
                $factory = $factory->withLanguages($areaLanguages->get('frontend'));
            }
            
            $factory = $factory->addEditableBlocks([
                'downloads' => Editable\Downloads::class,
                'hero' => Editable\Hero::class,
                'image' => Editable\Image::class,
                'image-gallery' => Editable\ImageGallery::class,
                'persons' => Editable\Persons::class,
                'text' => Editable\Text::class,
            ]);
            
            $factory = $factory->addBlockFactories([
                'downloads' => [Factory\Downloads::class, 'generateImagesInBackground' => false],
                'hero' => [Factory\Hero::class, 'generateImagesInBackground' => false],
                'image' => [Factory\Image::class, 'generateImagesInBackground' => false],
                'image-gallery' => [Factory\ImageGallery::class, 'generateImagesInBackground' => false],
                'persons' => [Factory\Persons::class, 'generateImagesInBackground' => false],
                'text' => Factory\Text::class,
            ]);
            
            $editor = $factory->createEditor(name: 'mail');
            return $editor;
        },
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    |
    | The migrations.
    |
    */
    
    'migrations' => [
        // Creates database tables depending on its storage
        // implemenation specified on the interfaces below.
        \Tobento\App\Block\Migration\BlockRepository::class,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Interfaces
    |--------------------------------------------------------------------------
    |
    | Do not change the interface's names!
    |
    */
    
    'interfaces' => [
        BlockRepositoryInterface::class =>
        static function(DatabasesInterface $databases, BlockEntityFactory $entityFactory): BlockRepositoryInterface {
            return new BlockStorageRepository(
                storage: $databases->default('storage')->storage()->new(),
                table: 'blocks',
                entityFactory: $entityFactory,
            );
        },
        
        \Tobento\App\Block\Block\Option\OptionsFactoryInterface::class => \Tobento\App\Block\Block\Option\OptionsFactory::class,
        
        EditableOptionsInterface::class => static function(): EditableOptionsInterface {
            return new EditableOptions([
                //'classes' => new EditableOption\Classes(),
                'margin' => new EditableOption\Margin(),
                'padding' => new EditableOption\Padding(),
                'color' => new EditableOption\Color(),
            ]);
        },
        
        //\Tobento\App\Block\ResourceResolverInterface::class => \Tobento\App\Block\ResourceResolver\Composite::class,
        \Tobento\App\Block\ResourceResolverInterface::class => \Tobento\App\Block\ResourceResolver\Slugs::class,
        /*\Tobento\App\Block\ResourceResolverInterface::class => static function() {
            return new \Tobento\App\Block\ResourceResolver\Composite(
                new \Tobento\App\Block\ResourceResolver\Slugs(),
            );
        },*/
    ],
];