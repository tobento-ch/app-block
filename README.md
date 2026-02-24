# App Block

The app block provides interfaces to create block editors. There are two [editors available](#available-editors), but you can easily [create your custom editor](#creating-custom-editor).

Editing blocks is kept simple having clients in minds. Furthermore, blocks use CSS classes only to style its content. This has multiple advantages:

* easily customize content by its classes
* using strong Content-Security-Policy blocking style-src
* limits user to keep corporate design

## Table of Contents

- [Getting Started](#getting-started)
    - [Requirements](#requirements)
- [Documentation](#documentation)
    - [App](#app)
    - [Block Boot](#block-boot)
        - [Block Config](#block-config)
    - [Available Editors](#available-editors)
        - [Default Editor](#default-editor)
            - [Configure Editor](#configure-editor)
            - [Render Editor](#render-editor)
            - [Saving Editor](#saving-editor)
        - [Mail Editor](#mail-editor)
            - [Configure Mail Editor](#configure-mail-editor)
            - [Render Mail Editor](#render-mail-editor)
            - [Saving Mail Editor](#saving-mail-editor)
    - [Crud Editor Field](#crud-editor-field)
    - [Block Views Editor Middleware](#block-views-editor-middleware)
    - [Available Blocks](#available-blocks)
        - [Downloads Block](#downloads-block)
        - [Hero Block](#hero-block)
        - [Image Block](#image-block)
        - [Image Gallery Block](#image-gallery-block)
        - [Persons Block](#persons-block)
        - [Text Block](#text-block)
    - [Block Options](#block-options)
    - [Available Block Options](#available-block-options)
        - [Classes Option](#classes-option)
        - [Color Option](#color-option)
        - [Layout Option](#layout-option)
        - [Margin And Padding Option](#margin-and-padding-option)
    - [Configurator](#configurator)
    - [Performance Notes](#performance-notes)
    - [Deleting Generated Pictures](#deleting-generated-pictures)
    - [Console](#console)
        - [Purge Blocks Command](#purge-blocks-command)
    - [Learn More](#learn-more)
        - [Creating Custom Editor](#creating-custom-editor)
        - [Adding Blocks Using Editor Factories](#adding-blocks-using-editor-factories)
- [Credits](#credits)
___

# Getting Started

Add the latest version of the app block project running this command.

```
composer require tobento/app-block
```

## Requirements

- PHP 8.4 or greater

# Documentation

## App

Check out the [**App Skeleton**](https://github.com/tobento-ch/app-skeleton) if you are using the skeleton.

You may also check out the [**App**](https://github.com/tobento-ch/app) to learn more about the app in general.

## Block Boot

The block boot does the following:

* installs and loads block config file
* implements needed interfaces
* add routes for the block editors

```php
use Tobento\App\AppFactory;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\EditorsInterface;
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\App\Block\ResourceResolverInterface;

// Create the app
$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Block\Boot\Block::class);
$app->booting();

// Implemented interfaces:
$blockRepository = $app->get(BlockRepositoryInterface::class);
$editors = $app->get(EditorsInterface::class);
$configurator = $app->get(ConfiguratorInterface::class);
$resourceResolver = $app->get(ResourceResolverInterface::class);

// Run the app
$app->run();
```

### Block Config

The configuration for the block is located in the ```app/config/block.php``` file at the default App Skeleton config location where you can configure the block editors for your application.

## Available Editors

### Default Editor

This editor is the default implementation.

#### Configure Editor

In the [Block Config](#block-config) you may configure the existing ```default``` editor or creating new editors using the ```EditorFactory::class```.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => Editable\Hero::class,
            'text' => Editable\Text::class,
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
            'text' => Factory\Text::class,
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

**EditorFactory Methods**

You may use the following methods to configure your editor to fit your requirements.

```php
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockRepositoryInterface;
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\App\Block\Editable;
use Tobento\App\Block\Factory;
use Tobento\Service\Language\LanguagesInterface;
        
// Set editable blocks returning a new instance:
$factory = $factory->withEditableBlocks([
    'text' => Editable\Text::class,
]);

// Set block factories returning a new instance:
$factory = $factory->withBlockFactories([
    'text' => Factory\Text::class,
]);

// Set block factory returning a new instance:
$factory = $factory->withBlockFactory($blockFactory); // BlockFactoryInterface

// You may set another block repository returning a new instance:
$factory = $factory->withBlockRepository($blockRepository); // BlockRepositoryInterface

// You may set another configurator returning a new instance:
$factory = $factory->withConfigurator($configurator); // ConfiguratorInterface

// You may set the available editor languages returning a new instance:
$factory = $factory->withLanguages($languages); // LanguagesInterface

// You may set another view namespace. Just make sure block views within that namespace exist.
$blockFactory = $factory->blockFactory()->withViewNamespace('mail');
$factory = $factory->withBlockFactory($blockFactory);
```

You may check out the [App Language](https://github.com/tobento-ch/app-language) to learn more about languages.

> **Warning**
> When using multiple languages, be sure to define language fallbacks. Certain blocks such as the [download block](#downloads-block) rely on these fallbacks to display correctly. Without a fallback, the download block may not appear at all unless the default language is active.

#### Render Editor

```php
use Tobento\App\Block\EditorsInterface;
use Tobento\Service\Responser\ResponserInterface;

$app->route('GET', 'example/editor', function(EditorsInterface $editors, ResponserInterface $responser) {
    $editor = $editors->get('default');
    
    // You may fetch existing blocks:
    $blocks = $editor->getBlockRepository()->findAll(where: [
        'id' => ['in' => [1,2]]
    ])->all();
    
    return $responser->render(
        view: 'example/editor',
        data: [
            'editor' => $editor,
            'blocks' => $blocks,
        ],
    );
});
```

In your view file, use the editor ```render``` method to render the editor with its blocks using views located in the ```views/block``` directory.

```php
echo $editor->render(
    id: 'unique',
    blocks: $blocks,
    options: [
        // you may set a block status:
        'status' => 'active', // pending is default
        
        // you may set a resource id and group:
        'resource_id' => 'articles:2',
        'resource_group' => 'main',
        
        // you may set a position
        'position' => 'header',
            
        // you may store blocks to a HTML input field
        'storeBlocksToInput' => 'blocks',
    ],
);

<input type="hidden" name="blocks">
```

You may check out the [Crud Editor Field](#crud-editor-field) or [Block Views Editor Middleware](#block-views-editor-middleware) section which provides two ways to integrate editors.

#### Saving Editor

Blocks will be stored using the ```BlockRepository::class``` with a ```pending``` status if not set otherwise. Its up to you changing the status other than ```pending```.

### Mail Editor

This editor will render blocks using views located in the ```views/block/mail``` directory.

#### Configure Mail Editor

In the [Block Config](#block-config) you may configure the existing ```mail``` editor or creating new editors using the ```MailEditorFactory::class``` which extends the ```EditorFactory::class```. So check out the [Configure Editor](#configure-editor) for its available methods.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Mail\MailEditorFactory;

'editors' => [
    'mail' => static function (MailEditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => Editable\Hero::class,
            'text' => Editable\Text::class,
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
            'text' => Factory\Text::class,
        ]);

        return $factory->createEditor(name: 'mail');
    },
],
```

#### Render Mail Editor

Check out the [Render Editor](#render-editor) section to learn more about it.

## Crud Editor Field

You may use the ```BlockEditor::class``` field to easily integrate a block editor when using the [App CRUD](https://github.com/tobento-ch/app-crud).

```php
use Tobento\App\Block\Crud\Field\BlockEditor;

new BlockEditor('blocks')->editor(name: 'default');
```

**Workflow**

Blocks will be stored using the block repository with a ```pending``` status when a CRUD resource has not been saved. Once the CRUD resource is saved, the status will be changed to ```active```. When a CRUD resource gets deleted, the status will be changed back to ```pending```. In addition, blocks will be stored in JSON format in the specified CRUD BlockEditor field.

To clean ```pending``` blocks consider using the [Purge Blocks Command](#purge-blocks-command).

**Render Blocks**

There are many ways how to render the stored blocks. One way is to use the editors block factory to render the created blocks stored in your CRUD resource.

```php
use Tobento\App\Block\EditorsInterface;

$editors = $app->get(EditorsInterface::class);
$editor = $editors->get('default');

$html = '';

foreach($storedBlocks as $block) {
    $block['editable'] = false; // set blocks as uneditable.
    $block['locale'] = 'de'; // you may change its locale.
    $entity = $editor->getBlockRepository()->createEntity($block);
    $html .= $editor->getBlockFactory()->createBlockFromEntity($entity)->render();
}

echo $html;
```

## Block Views Editor Middleware

The block views editor middleware integrates block editors based on the defined block views and the specified or resolved resource.

**Set up**

```php
use Tobento\App\AppFactory;
use Tobento\App\Block\Middleware\BlockViewsEditor;
use Tobento\Service\Responser\ResponserInterface;

// Create the app
$app = new AppFactory()->createApp();

// Add directories:
$app->dirs()
    ->dir(realpath(__DIR__.'/../'), 'root')
    ->dir(realpath(__DIR__.'/../app/'), 'app')
    ->dir($app->dir('app').'config', 'config', group: 'config')
    ->dir($app->dir('root').'public', 'public')
    ->dir($app->dir('root').'vendor', 'vendor');

// Adding boots
$app->boot(\Tobento\App\Block\Boot\Block::class);
$app->booting();

// Routes:
$app->route('GET', 'about', function(ResponserInterface $responser) {
    return $responser->render(
        view: 'about',
        data: [],
    );
})->middleware([
    BlockViewsEditor::class,
    'editorName' => 'default',
    'editable' => true,
    
    // you may set a resource id and/or group
    // modifying the resource resolved from the resource resolver:
    'resourceId' => 'about',
    'resourceGroup' => 'main',
]);

// Run the app
$app->run();
```

You may configure the resource resolver in the [Block Config](#block-config) file:

```php
'interfaces' => [
    \Tobento\App\Block\ResourceResolverInterface::class => \Tobento\App\Block\ResourceResolver\Slugs::class,
],
```

**View**

Views starting with ```blocks.resource``` are specific to its defined resource , meaning blocks will only be rendered on the matching resource. Views such as ```blocks.header``` and ```blocks.footer``` will always render its blocks within the same ```resourceGroup``` but indepedently of the ```resourceId```. You can define as many views you like.

```php
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>About</title>
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
    </head>
    <body>
        <header class="page-header">
            <?= $view->render('blocks.header') ?>
        </header>
        <main class="page-main">
            <?= $view->render('blocks.resource') ?>
            <p>Some content</p>
            <?= $view->render('blocks.resource.footer') ?>
        </main>
        <footer class="page-footer">
            <?= $view->render('blocks.footer') ?>
        </footer>
    </body>
</html>
```

**Deleting Blocks**

If your resource is a [App CRUD](https://github.com/tobento-ch/app-crud) being resolved by the slugs, you may use ```BlockResourceEditor::class``` field, which will delete blocks while deleting the field.

```php
use Tobento\App\Block\Crud\Field\BlockResourceEditor;
use Tobento\App\Crud\Entity\EntityInterface;

new BlockResourceEditor()
    ->editor('default')
    
    // Set the supported block positions:
    ->blockPositions('resource.header', 'resource')
    
    // You may customize the resource id:
    ->resourceId(fn (EntityInterface $entity): string => sprintf('articles:%s', $entity->id()))
    // By default, the CRUD resource name and the entity id is used e.g. 'articles:45'
    // matching the slugs resource resolver pattern.
    
    // You may set a resource group:
    ->resourceGroup(name: 'main')
    
    // You may disable blocks being editable as they are editable by the middleware:
    ->editable(false) // default true
    
    // Customize or disable the position title:
    ->positionTitle(false) // disable title completely
    // ->positionTitle('Content') // static title
    // ->positionTitle(fn(string $pos): null|string => sprintf('Position: %s', $pos))  // dynamic title
    
    // You may enable to store blocks on its field
    // (not recommended if using the middleware to edit blocks as data are not in sync):
    ->storable(true); // default false
```

Otherwise, you will need to implement your own logic using the block repository.

## Available Blocks

### Downloads Block

This block lets you add files to be displayed for download or be viewed in browser.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'downloads' => Editable\Downloads::class,
            
            // Or:
            'downloads' => new Editable\Downloads(
                // you may customize the picture definitions:
                pictureDefinitions: ['block-downloads'], // default
                
                // you may configure the allowed file extensions:
                allowedFileExtensions: ['jpg', 'png', 'webp', 'pdf'], // default
                
                // you may set the max number of files allowed:
                maxNumberOfFiles: 50, // default
            ),
        ]);

        $factory->addBlockFactories([
            'downloads' => Factory\Downloads::class,
            
            // you may generate images immediately:
            'downloads' => [Factory\Downloads::class, 'generateImagesInBackground' => false],
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

#### Requirements

**1. Create the `downloads` File Storage**

You must define a file storage named `downloads` of type [public](https://github.com/tobento-ch/service-file-storage#public-storage) in your `config/file_storage.php` file:

```php
'storages' => [

    'downloads' => [
        'factory' => \Tobento\App\FileStorage\FilesystemStorageFactory::class,
        'config' => [
            // The location storing the files:
            'location' => directory('app').'storage/downloads/',
            
            // Must be public (web-accessible).
            'storage_type' => 'public',
        ],
    ],
],
```

For more information on file storages, visit [App File Storage](https://github.com/tobento-ch/app-file-storage).

**2. Add the Storage to Media Features**

Next, ensure that the `downloads` storage is included in the `supportedStorages` parameter for the relevant features in your `config/media.php` file:

```php
'features' => [
    new Feature\File(
        supportedStorages: ['images', 'downloads'],
    ),
    new Feature\FileDownload(
        supportedStorages: ['downloads'],
    ),
    new Feature\FileDisplay(
        supportedStorages: ['downloads'],
    ),
],
```

For more details, see [App Media](https://github.com/tobento-ch/app-media).

### Hero Block

This block creates an editable text block using the [Js Editor](https://github.com/tobento-ch/js-editor) and lets you add an image to be displayed.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => Editable\Hero::class,
            
            // you may customize the picture definitions:
            'hero' => new Editable\Hero(
                pictureDefinitions: ['block-hero'], // default
            ),
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
            
            // you may generate images immediately:
            'hero' => [Factory\Hero::class, 'generateImagesInBackground' => false],
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

### Image Block

This block lets you add an image to be displayed.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'image' => Editable\Image::class,
            
            // you may customize the picture definitions:
            'image' => new Editable\Image(
                pictureDefinitions: ['block-image'], // default
            ),
        ]);

        $factory->addBlockFactories([
            'image' => Factory\Image::class,
            
            // you may generate images immediately:
            'image' => [Factory\Image::class, 'generateImagesInBackground' => false],
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

### Image Gallery Block

This block lets you add multiple images to be displayed as a gallery. Clicking on an image opens up a modal with bigger sized images.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'image-gallery' => Editable\ImageGallery::class,
            
            // Or:
            'image-gallery' => new Editable\ImageGallery(
                // you may customize the picture definitions:
                pictureDefinitions: [
                    'block-image-gallery', // default
                    'block-image-gallery-large', // you may add which is used for large images.
                ],
                
                // you may set the max number of images allowed:
                maxNumberOfImages: 50, // default
            ),
        ]);

        $factory->addBlockFactories([
            'image-gallery' => Factory\ImageGallery::class,
            
            // you may generate images immediately:
            'image-gallery' => [Factory\ImageGallery::class, 'generateImagesInBackground' => false],
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

### Persons Block

This block lets you add persons to be displayed. For instance, you add a team section. 

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'persons' => Editable\Persons::class,
            
            // you may customize the picture definitions:
            'persons' => new Editable\Persons(
                pictureDefinitions: ['block-persons'], // default
            ),
        ]);

        $factory->addBlockFactories([
            'persons' => Factory\Persons::class,
            
            // you may generate images immediately:
            'persons' => [Factory\Persons::class, 'generateImagesInBackground' => false],
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

### Text Block

This block creates an editable text block using the [Js Editor](https://github.com/tobento-ch/js-editor).

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'text' => Editable\Text::class,
        ]);

        $factory->addBlockFactories([
            'text' => Factory\Text::class,
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

## Block Options

You can configure the block options in the ```app/config/block.php``` file in the interfaces section:

```php
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;

'interfaces' => [
    \Tobento\App\Block\Block\Option\OptionsFactoryInterface::class => \Tobento\App\Block\Block\Option\OptionsFactory::class,

    EditableOptionsInterface::class => static function(): EditableOptionsInterface {
        return new EditableOptions([
            'padding' => new EditableOption\Padding(),
            'margin' => new EditableOption\Margin(),
            'color' => new EditableOption\Color(),
        ]);
    },
],
```

You may customize editable block options for each block separately:

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory, EditableOptionsInterface $editableOptions): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => new Editable\Hero(
                options: $editableOptions->withOption(
                    name: 'layout',
                    option: new EditableOption\Layout(['foo' => 'Foo'])
                ),
            ),
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

Available Methods:

```php
// Adds an option returning a new instance:
$editableOptions = $editableOptions->withOption(
    name: 'layout',
    option: new EditableOption\Layout(['foo' => 'Foo'])
);

// Returns a new instance ONLY with the specified options:
$editableOptions = $editableOptions->only('padding', 'margin');

// Returns a new instance EXCEPT with the specified options:
$editableOptions = $editableOptions->except('padding', 'margin');

// Returns a new instance with the options orderd by the specified names:
$editableOptions = $editableOptions->reorder('padding', 'margin');
```

## Available Block Options

### Classes Option

The classes option lets you select multiple CSS classes to be assigned on the block.

```php
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;

'interfaces' => [
    EditableOptionsInterface::class => static function(): EditableOptionsInterface {
        return new EditableOptions([
            'classes' => new EditableOption\Classes(
                // You may set custom classes, otherwise default are used:
                classes: ['classname' => 'A title'],
                
                // You may disable searching classes if you have only a few class:
                searchableClasses: false, // true is default
                
                // You may change the group name:
                groupName: 'Classes', // default
            ),
        ]);
    },
],
```

### Color Option

The color option lets you select a color for the background and text.

```php
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;

'interfaces' => [
    EditableOptionsInterface::class => static function(): EditableOptionsInterface {
        return new EditableOptions([
            'color' => new EditableOption\Color(
                supportedColors: ['text', 'background'] // default
            ),
        ]);
    },
],
```

### Layout Option

The layout option lets you define multiple layouts for the block if supported.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory, EditableOptionsInterface $editableOptions): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => new Editable\Hero(
                options: $editableOptions->withOption(
                    name: 'layout',
                    option: new EditableOption\Layout(['fit' => 'Fit Image'])
                ),
            ),
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

Make sure, you have created the corresponding view file like ```views/block/hero-fit``` and ```views/block/hero-editable-fit```, otherwise the default view file is used.

### Margin And Padding Option

The margin and padding option lets you select a margin and/or padding size.

```php
use Tobento\App\Block\Editable\Option as EditableOption;
use Tobento\App\Block\Editable\Option\Options as EditableOptions;
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;

'interfaces' => [
    EditableOptionsInterface::class => static function(): EditableOptionsInterface {
        return new EditableOptions([
            'margin' => new EditableOption\Margin(
                supportedMargin: ['top', 'bottom', 'left', 'right'], // default
            ),
            
            'padding' => new EditableOption\Padding(
                supportedPadding: ['top', 'bottom', 'left', 'right'], // default
            ),
        ]);
    },
],
```

## Configurator

You can create a new configurator class to customize or restrict blocks based on specific conditions.

**Creating Configurator**

```php
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\App\Block\EditableBlocksInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Http\Exception\HttpException;

class Configurator implements ConfiguratorInterface
{
    /**
     * Configure editable blocks.
     *
     * @param string $for
     * @param EditableBlocksInterface $blocks
     * @param array<string, mixed> $options
     * @return EditableBlocksInterface
     */
    public function configureEditableBlocks(string $for, EditableBlocksInterface $blocks, array $options): EditableBlocksInterface
    {
        // can only add the text and hero block on the resource position:
        if ($for === 'new' && $options['position'] === 'resource') {
            return $blocks->only('text', 'hero');
            // or using the except method:
            //return $blocks->except('text', 'hero');
        }
        
        return $blocks;
    }
    
    /**
     * Configure editable block buttons.
     *
     * @param array<string, string> $buttons
     * @param BlockEntityInterface $entity
     * @return array<string, string>
     */
    public function configureEditableBlockButtons(array $buttons, BlockEntityInterface $entity): array
    {
        // remove the delete button on the resource position:
        if ($entity->position() === 'resource') {
            unset($buttons['delete']);
            return $buttons;
        }

        return $buttons;
    }
    
    /**
     * Configure action fields.
     *
     * @param ActionInterface $action
     * @param FieldsInterface $fields
     * @return FieldsInterface
     * @throws HttpException
     */
    public function configureActionFields(ActionInterface $action, FieldsInterface $fields): FieldsInterface
    {
        $entity = $action->entity();
        
        if (
            in_array($action->name(), ['delete'])
            && $entity->get('position') === 'header'
        ) {
            throw new HttpException(statusCode: 403, message: 'blocks in the header section cannot be deleted at all.');
        }
        
        if (
            in_array($action->name(), ['edit', 'update', 'delete'])
            && $entity->get('id') === 12
        ) {
            throw new HttpException(statusCode: 403, message: 'block with the id 12 cannot be edited and deleted.');
        }
        
        return $fields;
    }
    
    /**
     * Configure create block.
     *
     * @param array<string, mixed> $block
     * @return array<string, mixed>
     */
    public function configureCreateBlock(array $block): array
    {
        return $block;
    }
    
    /**
     * Configure create block from entity.
     *
     * @param BlockEntityInterface $entity
     * @return BlockEntityInterface
     */
    public function configureCreateBlockFromEntity(BlockEntityInterface $entity): BlockEntityInterface
    {
        return $entity;
    }
}
```

**Add Configurator Using Editor Factory**

In the [Block Config](#block-config) you can add the configurator for each editor using the editor factory ```withConfigurator``` method:

```php
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
    
        $factory = $factory->withConfigurator(new Configurator());
        
        //...

        return $factory->createEditor(name: 'default');
    },
],
```

**Add Configurator Globally**

In the [Block Config](#block-config) you can add the configurator globally for all editors:

```php
'interfaces' => [
    \Tobento\App\Block\ConfiguratorInterface::class => Configurator::class,
],
```

## Performance Notes

Some block types (e.g. **Image**, **Image Gallery**, **Hero**, **Persons**) may require server-side image processing such as resizing, optimization, and thumbnail generation.  
These operations can be computationally expensive and may impact the perceived speed of certain actions.

### Editors in General

When editing a block that allows uploading images, the operation may take longer because two steps are involved:

1. The browser uploads the image file to the server.
2. The server generates all required image variants (resizing, optimization,
   thumbnails).

The editor waits for this process to complete before updating the block preview.
This ensures that the block displays with its final image dimensions and avoids
layout shifts during editing. If you prefer faster interactions, you may enable
background image generation (see next section), although the block may
temporarily display fallback images until processing is complete.

### CRUD Editor Field

When using the [Crud Editor Field](#crud-editor-field), the **copy** action may take longer than expected. This is because copying does not simply duplicate the existing block data. Instead, each block is fully recreated on the server using the same logic as when creating a new block. This includes image processing (resizing, optimization, thumbnail generation). Only after all blocks have been recreated does the page render with the updated block list.

This is especially noticeable for image-heavy blocks such as **Image Gallery**, which may generate multiple image variants per image. You may enable background image generation (see next section) to make the page load almost instantly.

Other CRUD actions such as **edit** behave the same way as described in the **Editors in General** section, since they also trigger image processing when images are uploaded or changed.

### Background Image Generation

You may enable background image generation in your block factories in the
[block config](#block-config) file:

```php
'generateImagesInBackground' => true
```

This makes UI interactions faster because the server no longer waits for image
processing to finish before returning a response. However, blocks may
temporarily display fallback images (using data: URLs) until the final image
variants are generated. Depending on your CSS, this can lead to minor layout
differences until processing is complete.

Background image generation requires a running queue worker, since image processing is handled asynchronously.  

For more details on how images are generated, see the  
[Picture Feature documentation](https://github.com/tobento-ch/app-media#picture-feature).

To learn how to run queue workers, see the  
[Queue documentation](https://github.com/tobento-ch/app-queue#running-queues).


## Deleting Generated Pictures

Blocks such as the [Image Block](#image-block) generate pictures using the [Media Picture Feature](https://github.com/tobento-ch/app-media#picture-feature).

To clear generated pictures, once a block is updated or deleted, you will need to define an event listener in the ```app/config/event.php``` file:

```php
'listeners' => [
    \Tobento\App\Crud\Event\FileSourceDeleted::class => [
        \Tobento\App\Crud\Listener\DeletesGeneratedPictures::class,
    ],
],
```

## Console

### Purge Blocks Command

Use the following command to purge ```pending``` blocks:

```
php ap blocks:purge
```

If you would like to automate this process, consider installing the [App Schedule](https://github.com/tobento-ch/app-schedule) bundle and using a command task:

```php
use Tobento\Service\Schedule\Task;
use Butschster\CronExpression\Generator;

$schedule->task(
    new Task\CommandTask(
        command: 'php ap blocks:purge',
    )
    // schedule task:
    ->cron(Generator::create()->daily())
);
```

## Learn More

### Creating Custom Editor

**Option 1** with view namespace only

In the [Block Config](#block-config) file just add a custom ```viewNamespace```.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'custom' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => Editable\Hero::class,
            'text' => Editable\Text::class,
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
            'text' => Factory\Text::class,
        ]);
        
        // add custom namespace
        $blockFactory = $factory->blockFactory()->withViewNamespace('custom');
        $factory = $factory->withBlockFactory($blockFactory);

        return $factory->createEditor(name: 'custom');
    },
],
```

Finally, add the view files in the ```viewNamespace``` defined which you want to customize skipping others you do not want to customize.

```
views/block/custom/
    hero.php
    hero-editable.php
    ...
```

**Option 2** with custom editor factory

By creating a custom editor factory, you will be able to [add blocks using the factory](#adding-blocks-using-editor-factories).

First, create the editor factory by extending the ```EditorFactory::class```:

```php
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\Editor\BlockFactory;
use Tobento\App\Block\Editor\EditorFactory;

class CustomEditorFactory extends EditorFactory
{
    /**
     * Returns the created block factory.
     *
     * @return BlockFactoryInterface
     */
    protected function createBlockFactory(): BlockFactoryInterface
    {
        return new BlockFactory(
            container: $this->container,
            configurator: $this->configurator(),
            viewNamespace: 'custom',
        );
    }
}
```

Next, add the view files in the ```viewNamespace``` defined which you want to customize skipping others you do not want to customize.

```
views/block/custom/
    hero.php
    hero-editable.php
    ...
```

Finally, configure your editor in the [Block Config](#block-config) file.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'custom' => static function (CustomEditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'hero' => Editable\Hero::class,
            'text' => Editable\Text::class,
        ]);

        $factory->addBlockFactories([
            'hero' => Factory\Hero::class,
            'text' => Factory\Text::class,
        ]);

        return $factory->createEditor(name: 'custom');
    },
],
```

### Adding Blocks Using Editor Factories

It may be useful to add blocks using the editor factories from within the app if you have different components such as a Shop component providing specific shop blocks.

```php
use Tobento\App\Block\Editor\EditorFactory;

$app->on(
    EditorFactory::class,
    static function(EditorFactory $factory): void {
        $factory->addEditableBlocks([
            'products' => ProductListEditable::class,
        ]);

        $factory->addBlockFactories([
            'products' => ProductListFactory::class,
        ]);
    }
);
```

# Credits

- [Tobias Strub](https://www.tobento.ch)
- [All Contributors](../../contributors)