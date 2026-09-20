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
        - [FAQ Block](#faq-block)
        - [Hero Block](#hero-block)
        - [Image Block](#image-block)
        - [Image Gallery Block](#image-gallery-block)
        - [Persons Block](#persons-block)
        - [Text Block](#text-block)
    - [Creating Custom Blocks](#creating-custom-blocks)
        - [Creating Blocks](#creating-blocks)
        - [Using AbstractFields](#using-abstractfields)
        - [Using AbstractItems](#using-abstractitems)
        - [Using AbstractRepository](#using-abstractrepository)
    - [Available Fields](#available-fields)
    - [Block Options](#block-options)
    - [Available Block Options](#available-block-options)
        - [Classes Option](#classes-option)
        - [Color Option](#color-option)
        - [Layout Option](#layout-option)
        - [Margin And Padding Option](#margin-and-padding-option)
    - [Configurator](#configurator)
    - [Available Configurators](#available-configurators)
        - [Null Configurator](#null-configurator)
        - [Owner Configurator](#owner-configurator)
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

new BlockEditor('blocks')->editor(
    name: 'default',
    owner: 'articles', // or null
);
```

### What the `owner` parameter does

The `owner` identifies **which CRUD resource** the blocks belong to.  
It is stored on each block entity and used by owner-aware configurators implementing:

```php
use Tobento\App\Block\OwnerConfiguratorInterface;

interface OwnerConfiguratorInterface
{
    public function owns(BlockEntityInterface $entity): bool;
}
```

This allows:

- filtering blocks by owner  
- enforcing ACL rules  
- restricting block types per owner  
- isolating blocks between different CRUD resources

See also: **[Owner Configurator](#owner-configurator)** for detailed ownership rules and configurator delegation.

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
use Tobento\App\Block\Editable\Option\OptionsInterface as EditableOptionsInterface;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

'editors' => [
    'default' => static function (EditorFactory $factory, EditableOptionsInterface $editableOptions): EditorInterface {
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
                
                // you may define editor options shown in the block settings panel:
                options: $editableOptions->withOption(
                    name: 'layout',
                    option: new EditableOption\Layout(
                        options: ['table' => 'Table'],
                        emptyLabel: 'Cards',
                    ),
                ),
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

### FAQ Block

The FAQ block lets you create a list of questions and answers that can be displayed anywhere on your site. It is ideal for support pages, product information, or any section where structured Q&A content is needed.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'faq' => Editable\Faq::class,
        ]);

        $factory->addBlockFactories([
            'faq' => Factory\Faq::class,
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

### Hero Block

This block creates an editable text block using the [Js Editor](https://github.com/tobento-ch/js-editor) and lets you add an image to be displayed.

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

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
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

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
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

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
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

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
use Tobento\App\Block\Editor\EditorFactory;
use Tobento\App\Block\Factory;

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

## Creating Custom Blocks

### Creating Blocks

This section explains how to create blocks manually without using helper
abstractions such as [AbstractFields](#using-abstractfields), [AbstractItems](#using-abstractitems), or [AbstractRepository]((#using-abstractrepository)).  
A block consists of three parts:

1. **Editable Block** - defines the fields shown in the Block editor UI  
2. **Block Factory** - hydrates the editable data into a renderable block
3. **Block** - renders the final HTML output

#### Creating Editable Block

Editable blocks implement `EditableBlockInterface`.  
They define:

- the block title, description, and icon  
- the default block data  
- the editable CRUD fields  
- how block data is mapped to fields (`toFields`)  

Example:

```php
namespace Tobento\App\Block\Editable;

use Tobento\App\Block\Editable\Option\OptionsInterface;
use Tobento\App\Block\EditableBlockInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

final class Text implements EditableBlockInterface
{
    public function __construct(
        private OptionsInterface $options,
    ) {}

    public function title(): string
    {
        return trans('Text');
    }

    public function description(): string
    {
        return trans('Add a text section.');
    }

    public function icon(): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" ...></svg>';
    }

    public function defaultBlock(): array
    {
        return ['type' => 'text', 'translation' => ['en' => '']];
    }

    public function configureFields(ActionInterface $action): iterable|FieldsInterface
    {
        return [
            new Field\Textarea(name: 'translation', label: trans('Text'))
                ->group(trans('Text'))
                ->validate('string')
                ->translatable(),
            ...$this->options->configureFields($action, $this),
        ];
    }

    public function toFields(array $block, ActionInterface $action): array
    {
        return $block;
    }
}
```

##### About `toFields()`

`toFields()` prepares the stored block data so the editor fields receive the **correct input format**.

For file fields (`Field\FileSource`), this matters because the editor must **not** get the stored `src` value. If `src` is present during an update, the validator thinks:

- the user did not upload a file
- but the field is required
- **validation error: image is required**

So for image blocks, `toFields()` must remove `src` on update:

```php
if ($action->name() === 'update') {
    unset($block['data']['image']['src']);
}
```

This ensures the editor keeps the existing file instead of treating the field as empty.

On store (duplicate), the stored file format must be normalized:

```php
if ($action->name() === 'store') {
    $block['data']['image'] = $this->normalizeFileSource($block['data']['image']);
}
```

In short:

- **update**: remove `src` to prevent image required validation errors
- **store**: normalize to ensure duplicated blocks load correctly

Simple blocks (like Text) don't need mapping, so they just return `$block`.

#### Creating Block Factory

The block factory transforms editable block data into a renderable block instance.  
It implements `BlockFactoryInterface`.

A factory decides:

- which view to use (editable or default)
- how options are created
- how block data is mapped to the final block
- how entities are converted into blocks

Example:

```php
namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Block\Option\OptionsFactoryInterface;
use Tobento\App\Block\Block;
use Tobento\App\Block\BlockEntityInterface;
use Tobento\App\Block\BlockFactoryInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\App\Block\Exception\BlockCreateException;
use Tobento\Service\View\ViewInterface;

final class Text implements BlockFactoryInterface
{
    public function __construct(
        private ViewInterface $view,
        private OptionsFactoryInterface $optionsFactory,
        private null|string $viewNamespace = null,
    ) {}

    public function withViewNamespace(null|string $namespace): static
    {
        $new = clone $this;
        $new->viewNamespace = $namespace;
        return $new;
    }

    public function viewNamespace(): null|string
    {
        return $this->viewNamespace;
    }

    public function createBlock(array $block): BlockInterface
    {
        $viewName = 'block/text-editable';

        if (($block['editable'] ?? true) === false) {
            $viewName = 'block/text';
        }

        $options = $this->optionsFactory->createOptions($block['options'] ?? []);

        $viewName = Helper::resolveViewName(
            view: $this->view,
            name: $viewName,
            namespace: $this->viewNamespace(),
            options: $options,
        );

        return new Block\Text(
            view: $this->view,
            options: $options,
            html: $block['html'] ?? '',
            viewName: $viewName,
        );
    }

    public function createBlockFromEntity(BlockEntityInterface $entity): BlockInterface
    {
        return $this->createBlock(block: [
            'type' => $entity->type(),
            'html' => $entity->localized('translation'),
            'options' => $entity->options(),
            'editable' => $entity->editable(),
        ]);
    }
}
```

This factory:

- selects the correct view (text-editable or text)
- resolves view namespaces
- creates block options
- maps editable block data to the final block
- supports entity hydration via `createBlockFromEntity()`

##### About `createBlockFromEntity()`

When loading a block from the repository, the factory must convert the stored
entity values into the format expected by `createBlock()`.

```php
public function createBlockFromEntity(BlockEntityInterface $entity): BlockInterface
{
    return $this->createBlock(block: [
        'type'     => $entity->type(),
        'html'     => $entity->localized('translation'),
        'options'  => $entity->options(),
        'editable' => $entity->editable(),
    ]);
}
```

The important part is:

```php
$entity->localized('translation')
```

This returns the correct string for the current locale, regardless of how the value is stored internally.

##### About `localized()`

`localized()` ensures you always get a string for the active locale, even if the stored value is:

- a plain string
- a `StringTranslations` object
- a JSON array of locales
- missing or malformed

Short behavior summary:

```php
use Tobento\Service\Repository\Storage\Attribute\StringTranslations;

public function localized(string $name): string
{
    $value = $this->get($name);
    $locale = $this->locale();

    // stored as plain string: return directly
    if (is_string($value)) {
        return $value;
    }

    // stored as StringTranslations: use its getter
    if ($value instanceof StringTranslations) {
        return $value->get(locale: $locale);
    }

    // stored as array: return $value[$locale] if exists
    if (is_array($value) && isset($value[$locale])) {
        return $value[$locale];
    }

    // fallback: first available locale or empty string
    return (string)($value[array_key_first($value)] ?? '');
}
```

##### Why this matters

Without `localized()`:

- multilingual blocks would need manual locale handling
- factories would need to check array formats themselves
- blocks could break when translations are missing

With `localized()`:

- every block factory always receives a clean string
- no matter how translations are stored
- no matter which locale is active

This keeps block factories simple and predictable.

#### Creating Block (rendering)

The final block implements `BlockInterface` and is responsible for producing HTML.  
Your factory returns a block class such as `Block\Text`.

Example:

```php
namespace Tobento\App\Block\Block;

use Tobento\App\Block\Block\Option\OptionsInterface;
use Tobento\App\Block\BlockInterface;
use Tobento\Service\View\ViewInterface;

class Text implements BlockInterface
{
    public function __construct(
        protected ViewInterface $view,
        protected OptionsInterface $options,
        protected string $html,
        protected null|string $viewName = null,
    ) {}

    public function render(): string
    {
        $view = $this->viewName ?: 'block/text';

        return $this->view->render(view: $view, data: ['block' => $this]);
    }

    public function html(): string
    {
        return $this->html;
    }

    public function options(): OptionsInterface
    {
        return $this->options;
    }
}
```

**Example views:**

`block/text`

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-text', 'content']);
?>
<div<?= $attributes ?>><?= $view->sanitizeHtml($block->html()) ?></div>
```

`block/text-editable`

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-text', 'content']);
?>
<div<?= $attributes ?>>
    <div data-editor><?= $view->sanitizeHtml($block->html()) ?></div>
</div>
```

##### Editable Views

Editable views (e.g. `block/text-editable`) are only required when a block needs an **interactive editing interface** inside the block's edit-mode render.

Examples include:

- inline text editing
- any interactive UI that must appear *inside* the block during editing  

In these cases, the editable view wraps the block's content in an element that activates the editor, for example:

```html
<div data-editor>...</div>
```

Blocks that do not require such interactive editing do not need a separate editable view and can use the same view for both edit and default modes.

#### Block Storage Columns

Blocks may store their data in several different column types depending on their structure and translation requirements. The following columns are commonly used by block factories and block entities.

##### `data` (Json)

Stores structured block data such as configuration, items, or nested values.

```php
new Column\Json('data')
```

Use this column for:

- repeatable items (e.g., FAQ items, gallery items)
- block-specific configuration values
- nested arrays or objects

This column is ideal for blocks that require flexible, schema-less storage.

##### `content` (Text)

Stores raw text or HTML content.

```php
new Column\Text(name: 'content', type: 'text')
```

Use this column for:

- Markdown converted to HTML
- any non-translatable text content

This column is not locale-aware. If you need translations, use `translation` or `translations`.

##### `translation` (Translatable, string)

Stores **one translatable string field** for the block.

```php
new Column\Translatable(name: 'translation', subtype: 'string')
```

Use this column when your block has a single main text value that should be localized. This is typically the block's primary text content (e.g. the text of a Text block, or the main text of a Hero block).

Example stored structure:

```json
{
    "translation": {
        "en": "Hello",
        "de": "Hallo"
    }
}
```

Blocks may still have additional translatable fields (e.g. image alt text), but those are stored inside the block's `data` column, not in `translation`.

##### `translations` (Translatable, array)

Stores multiple translatable fields grouped **by locale**.

```php
new Column\Translatable(name: 'translations', subtype: 'array')
```

Use this column when the block has several related translatable fields, such as:

- hero blocks (title, subtitle)
- card blocks (title, description)
- multi-field text blocks

Stored structure:

```json
{
    "translations": {
        "en": {
            "title": "Welcome",
            "subtitle": "Our mission"
        },
        "de": {
            "title": "Willkommen",
            "subtitle": "Unsere Mission"
        }
    }
}
```

#### Summary

| Column | Type | Purpose |
|---|---|---|
| `data` | JSON | Structured block data, items, configuration |
| `content` | Text | Raw HTML/text, non-translatable content |
| `translation` | Translatable string | Single translatable field |
| `translations` | Translatable array | Multiple translatable fields grouped by locale |

### Using AbstractFields

#### Creating Editable Block

Editable blocks based on `AbstractFields` are the simplest type of blocks.  
They consist of a fixed set of CRUD fields that the editor displays and stores.  
Blocks such as Hero, Image and Text are built using this base class.

To create an editable block, extend `AbstractFields` and implement:

- `type()` - the unique block identifier  
- `title()` - the human-readable name shown in the editor  
- `description()` - a short explanation for editors  
- `icon()` - an SVG icon (HTML-escaped)  
- `configureBlockFields()` - the CRUD fields used to edit the block  

Below is a minimal example based on the built-in **Hero** block:

```php
declare(strict_types=1);

namespace App\Block;

use Tobento\App\Block\Editable\AbstractFields;
use Tobento\App\Block\Editable\Option\OptionsInterface;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

class Hero extends AbstractFields
{
    public function __construct(
        protected OptionsInterface $options,
        protected array $pictureDefinitions = ['block-hero'],
    ) {}

    public function type(): string
    {
        return 'hero';
    }

    protected function configureBlockFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\File(name: 'data.image', label: trans('Image'))
            ->group(trans('Image'))
            ->translatable()
            ->fileSource(function(Field\FileSource $fs): void {
                $fs->storage(name: 'uploads-public');
                $fs->allowedExtensions('jpg', 'png', 'webp');
                $fs->pictureEditor(template: 'default', definitions: $this->pictureDefinitions);
            })
            ->fields(
                new Field\Text('alt', trans('Alternative Text'))
                    ->validate('string|htmlclean')
                    ->translatable(),
            )
            ->storeFilenameTo('alt');

        yield new Field\Textarea(name: 'translation', label: trans('Text'))
            ->group(trans('Text'))
            ->validate('string')
            ->translatable();
    }

    public function title(): string
    {
        return trans('Hero');
    }

    public function description(): string
    {
        return trans('A Hero section to get users attention, ideally with a call to action button.');
    }

    public function icon(): string
    {
        return '<svg ...></svg>';
    }
}
```

**Notes**

- `configureBlockFields()` may yield any supported CRUD field type.
- File fields are automatically normalized by `AbstractFields` during store and update.
- Options (layout, classes, margin, etc.) are automatically appended via `$this->options->configureFields()`.
- The block editor will render all editable fields returned by `configureFields()`.

#### Creating Block Factory

A block factory is responsible for transforming raw editable block data into renderable block field objects.  
Factories based on `Tobento\App\Block\Factory\AbstractFields` hydrate the fields defined in the editable block and map them to the correct renderable field classes.

To create a factory for an `AbstractFields`-based block:

- extend `Tobento\App\Block\Factory\AbstractFields`
- implement:
  - `type()`  
  - `configureFieldMapping()`  
  - `configureEntityFieldMapping()`  
  - `configureTranslatableFields()`  
  - optionally: `configureImageDefinitions()`  
  - optionally: `viewName()`  

Below is the factory for the built-in **Hero** block:

```php
declare(strict_types=1);

namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;

class Hero extends AbstractFields
{
    public function type(): string
    {
        return 'hero';
    }

    protected function configureFieldMapping(): iterable
    {
        return [
            'data.image'  => Field\Image::class,
            'translation' => Field\HtmlTextEditor::class,
        ];
    }

    protected function configureEntityFieldMapping(): array
    {
        return [
            'data.image'  => 'data.image',
            'translation' => 'translation',
        ];
    }

    protected function configureTranslatableFields(): array
    {
        return [
            'data.image.src',
            'data.image.alt',
            'data.image.figcaption',
            'translation',
        ];
    }

    protected function configureImageDefinitions(): array
    {
        return [
            'data.image' => 'block-hero',
        ];
    }

    public function viewName(): string
    {
        return 'block/hero';
    }
}
```

**Notes**

- `configureFieldMapping()` maps editor field names to renderable field classes.
- `configureEntityFieldMapping()` maps block field names to entity storage keys.
- `configureTranslatableFields()` defines which fields support localization.
- `configureImageDefinitions()` assigns picture definitions for image generation.
- `viewName()` determines the base template used when rendering the block.

For a complete overview of all renderable field classes that can be used in
`configureFieldMapping()`, see the [Available Fields](#available-fields) section.

#### Rendering Block

Blocks created by `Tobento\App\Block\Factory\AbstractFields` are rendered using
`Tobento\App\Block\Block\Fields`. This block represents a set of named
`FieldInterface` instances and provides helpers for rendering them in both
editable mode and default mode.

Each field is responsible for generating its own HTML output. The `Fields` block
coordinates *how* fields are rendered and provides a consistent API for views.

**renderField()**

The `renderField()` method automatically selects the correct rendering mode:

- **editable mode** `FieldInterface::renderEditable()`  
- **default mode** `FieldInterface::render()`  

This allows views to remain simple:

```php
<?= $block->renderField($field) ?>
```

Views do not need to handle rendering mode themselves.  
The field decides how to output its content based on the block's internal state.

##### Default Fields View

If no custom view is defined, the block uses the default template `block/fields`,
which loops through all fields in the order they were hydrated:

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-fields']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->fields()->all() as $field) { ?>
        <div class="block-field">
            <?= $block->renderField($field) ?>
        </div>
    <?php } ?>
</div>
```

This is ideal for simple blocks where fields should appear sequentially.

##### Custom Block View (Hero Example)

More advanced blocks define their own view and place fields manually:

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-hero']);
$fields = $block->fields();
?>
<div<?= $attributes ?>>
    <div class="hero-body">
        <div class="content"><?= $block->renderField($fields->get('translation')) ?></div>
    </div>
    <div class="hero-media"><?= $block->renderField($fields->get('data.image')) ?></div>
</div>
```

This demonstrates:

- accessing fields by name
- rendering fields in custom layout regions
- combining block options with custom HTML structure

##### Fields Accessor

`$block->fields()` returns an anonymous accessor object with:

- `has($name)` - check if a field exists
- `get($name)` - return a field or [Null Field](#fieldnullfield)
- `data($name)` - return a [Data Field](#fielddata)
- `all()` - return all fields

Example:

```php
$fields = $block->fields();

if ($fields->has('translation')) {
    echo $block->renderField($fields->get('translation'));
}
```

If a field does not exist, `get()` returns a `NullField`, which safely renders empty output.

Example: Accessing data fields

```php
$display = $fields->data('data.display');

if ($display->contains('image')) {
    // The user enabled the "Preview Image" option
}
```

For more info see: [Data Field](#fielddata)

##### Block Options (in views)

`$block->options()` returns an `OptionsInterface` instance used to generate HTML attributes:

```php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-hero']);
```

Options may include:

- CSS classes
- spacing
- layout modifiers
- view overrides

For details, see [Block Options](#block-options)

##### Notes

- Always use `renderField()` instead of calling field methods directly.
- Custom views should access fields via `$block->fields()->get('name')`.
- The block's view name is resolved automatically by the factory.
- `NullField` ensures missing fields never break rendering.

For a complete list of all field classes that can be used in block factories and rendered via `renderField()`, see the [Available Fields](#available-fields) section.

### Using AbstractItems

#### Creating Editable Block

Editable blocks based on `AbstractItems` are designed for **repeatable item
lists** - blocks where the editor manages a collection of entries that each
share the same set of fields. Blocks such as FAQ, Features, and Team Members
are built using this base class.

To create an editable item-list block, extend `AbstractItems` and implement:

- `type()` - the unique block identifier
- `title()` - the human-readable name shown in the editor
- `description()` - a short explanation for editors
- `icon()` - an SVG icon (HTML-escaped)
- `configureItemFields()` - the CRUD fields used to edit **each item**

Below is the built-in **FAQ** block:

```php
declare(strict_types=1);

namespace Tobento\App\Block\Editable;

use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field;
use Tobento\App\Crud\Field\FieldInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use function Tobento\App\Translation\trans;

class Faq extends AbstractItems
{
    protected function configureItemFields(ActionInterface $action): iterable|FieldsInterface
    {
        yield new Field\Text(name: 'question', label: trans('Question'))
            ->validate('required|htmlclean|maxLen:250')
            ->translatable();

        yield new Field\Textarea(name: 'answer', label: trans('Answer'))
            ->validate('required|string|maxLen:5000')
            ->translatable();
    }

    public function type(): string
    {
        return 'faq';
    }

    public function title(): string
    {
        return trans('FAQ');
    }

    public function description(): string
    {
        return trans('Frequently asked questions.');
    }

    public function icon(): string
    {
        return '<svg ...></svg>';
    }
}
```

**Notes**

- `configureItemFields()` defines the fields repeated for **every item**, not the block as a whole.
- Only a limited set of field types is allowed inside items: `Checkboxes`, `File`, `FileSource`, `Html`, `Options`, `Radios`, `Select`, `SingleOptions`, `Text`, `Textarea`, and `Value`. Using an unsupported field type throws an `InvalidArgumentException`.
- `maxItems()` limits how many items an editor may add (default `50`).
- `defaultItems()` sets how many empty items are shown when the block is first created (default `1`).
- `addNewItemText()` controls the label of the "add item" button.
- File fields inside items are automatically normalized during store and update, the same way single file fields are handled in [`toFields()`](#about-tofields).
- Options (layout, classes, margin, etc.) are automatically appended via `$this->options->configureFields()`.

#### Creating Block Factory

A block factory for item-list blocks is responsible for hydrating each item's
raw CRUD data into typed, renderable field objects. Factories based on
`Tobento\App\Block\Factory\AbstractItems` handle this hydration for you.

To create a factory for an `AbstractItems`-based block:

- extend `Tobento\App\Block\Factory\AbstractItems`
- implement:
  - `type()`
  - `configureFieldMapping()`
  - `configureTranslatableFields()`
  - optionally: `viewName()`

Below is the factory for the built-in **FAQ** block:

```php
declare(strict_types=1);

namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;

class Faq extends AbstractItems
{
    protected function configureFieldMapping(): iterable
    {
        return [
            'question' => Field\Text::class,
            'answer'   => Field\HtmlTextEditor::class,
        ];
    }

    protected function configureTranslatableFields(): array
    {
        return ['question', 'answer'];
    }

    public function type(): string
    {
        return 'faq';
    }
}
```

**Notes**

- `configureFieldMapping()` maps each item's field names to the renderable field classes used to render them.
- `configureTranslatableFields()` defines which item fields support localization; only fields listed here are resolved to the current locale via `localizeItems()`.
- `type()` must match the type returned by the corresponding editable block so the block manager can correctly pair editable configuration, hydration, and rendering.
- `viewName()` defaults to `block/items` and determines the base template used to render the block. Override it if your block needs a different base view.
- Supported field mappings out of the box are `Field\Text`, `Field\Html`, `Field\HtmlTextEditor`, `Field\Image`, `Field\File`, and `Field\FileSource`. Unmapped or unrecognized classes are silently skipped during hydration.

For a complete overview of all renderable field classes that can be used in
`configureFieldMapping()`, see the [Available Fields](#available-fields) section.

#### Rendering Block

Blocks created by `Tobento\App\Block\Factory\AbstractItems` are rendered using
`Tobento\App\Block\Block\Items`. This block represents a collection of items,
where each item is a set of named `FieldInterface` instances, and provides
helpers for rendering them in both editable mode and default mode.

**renderField()**

Just like the `Fields` block, `Items` exposes `renderField()`, which
automatically selects the correct rendering mode:

- **editable mode** `FieldInterface::renderEditable()`
- **default mode** `FieldInterface::render()`

```php
<?= $block->renderField($field) ?>
```

Views never need to check whether the block is currently being edited.

##### Default Items View

If no custom view is defined, the block uses the default template
`block/items`, which loops through all items and renders each item's fields:

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-items', 'cards', 'cards-small']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="card">
            <div class="card-body">
                <?php foreach ($item->all() as $field) { ?>
                    <div class="block-items-field">
                        <?= $block->renderField($field) ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>
```

This default is ideal for simple item blocks where every field can be rendered the same way, without distinguishing its role.

##### Custom Items View (FAQ Example)

Blocks whose fields have distinct roles - such as FAQ, where a question and
its answer should be laid out differently - define their own view and place
fields manually:

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-faq']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="faq-item">
            <div class="faq-question"><?= $block->renderField($item->get('question')) ?></div>
            <div class="faq-answer"><?= $block->renderField($item->get('answer')) ?></div>
        </div>
    <?php } ?>
</div>
```

This demonstrates:

- accessing an item's fields by name via `get()`
- rendering fields into distinct, semantically meaningful layout regions
- combining block options with a custom item structure

Use the default `block/items` view for uniform item lists, and a custom view like this whenever an item's fields play different visual or semantic roles.

##### Items Accessor

`$block->items()` returns an array of anonymous item accessor objects, each
exposing:

- `has($name)` - check if a field exists on the item
- `get($name)` - return the field, or `NullField` if it doesn't exist
- `data($name)` - return a [Data Field](#fielddata)
- `all()` - return all fields on the item

Example:

```php
foreach ($block->items() as $item) {
    if ($item->has('question')) {
        echo $block->renderField($item->get('question'));
    }
}
```

If a field does not exist, `get()` returns a `NullField`, which safely renders
empty output - the same behavior as the `Fields` block's accessor.

##### Notes

- Always use `renderField()` instead of calling field methods directly.
- Custom views iterate `$block->items()` and access each item's fields via `get('name')`.
- The block's view name is resolved automatically by the factory, the same way as [Block Options](#block-options) and view namespaces work for `AbstractFields`-based blocks.

For a complete list of all field classes that can be used in block factories and rendered via `renderField()`, see the [Available Fields](#available-fields) section.

### Using AbstractRepository

#### Creating Editable Block

Editable blocks based on `AbstractRepository` are designed for blocks that
**list items pulled from a repository** - for example a list of the latest
articles, products, or events - rather than data entered directly by the
editor. The block only lets the editor configure *how* items are selected
(sorting, limit, categories, or specific items), not the item content itself.

To create an editable repository-driven block, extend `AbstractRepository`
and implement:

- `type()` - the unique block identifier
- `title()` - the human-readable name shown in the editor
- `description()` - a short explanation for editors
- `icon()` - an SVG icon (HTML-escaped)
- `itemRepository()` - the repository class used to fetch items
- `itemBaseWhere()` - base where conditions applied to every item query
- `itemToOption()` - converts a repository item into a selectable option
- `taxonomyRepository()` - the taxonomy repository class, or `null` if not used
- `taxonomyBaseWhere()` - base where conditions applied to taxonomy queries
- `taxonomyItemToOption()` - converts a repository taxonomy item into a selectable option

Below is an example for an **Articles** block:

```php
declare(strict_types=1);

namespace App\Block;

use Tobento\App\Block\Editable\AbstractRepository;
use Tobento\App\Crud\Field;
use function Tobento\App\Translation\trans;

class Articles extends AbstractRepository
{
    public function type(): string
    {
        return 'articles';
    }

    public function title(): string
    {
        return trans('Articles');
    }

    public function description(): string
    {
        return trans('Displays a list of articles.');
    }

    public function icon(): string
    {
        return '<svg ...></svg>';
    }

    protected function itemRepository(): string
    {
        return ArticleRepository::class;
    }

    protected function itemBaseWhere(): array
    {
        return ['status' => 'published'];
    }

    protected function itemToOption(object $item): Field\Option
    {
        return new Field\Option(value: $item->id(), text: $item->title());
    }

    protected function taxonomyRepository(): null|string
    {
        return CategoryRepository::class;
    }

    protected function taxonomyBaseWhere(): array
    {
        return [];
    }

    protected function taxonomyItemToOption(object $item): Field\Option
    {
        return new Field\Option(value: $item->id(), text: $item->name());
    }
}
```

**Notes**

- The built-in fields (`sortBy`, `limit`, `taxonomy`, `items`) are added automatically based on `withDefaultFields()`; by default, all four are enabled.
- `withDefaultFields()` returns a new instance limited to the specified field names, letting you disable fields your block doesn't need, e.g. `$editable->withDefaultFields('limit', 'items')` to drop sorting and taxonomy selection.
- The `taxonomy` field is only added when both `taxonomyRepository()` returns a class **and** the field is included in `defaultFieldNames`.
- The `items` field lets editors pick specific items directly, in addition to (or instead of) taxonomy-based filtering.
- Options (layout, classes, margin, etc.) are automatically appended via `$this->options->configureFields()`.

#### Creating Block Factory

A block factory for repository-driven blocks is responsible for resolving
the actual repository services, applying locale, filtering, sorting, and
taxonomy resolution, then handing the result to the block for rendering.
Factories based on `Tobento\App\Block\Factory\AbstractRepository` handle
this for you.

To create a factory for an `AbstractRepository`-based block:

- extend `Tobento\App\Block\Factory\AbstractRepository`
- implement:
  - `type()`
  - `viewName()`
  - `itemRepository()`
  - `itemBaseWhere()`
  - `itemsOrderByResolver()`
  - `taxonomyRepository()`
  - `taxonomyBaseWhere()`
  - `taxonomyIdsResolver()`

Below is an example factory for the **Articles** block:

```php
declare(strict_types=1);

namespace Tobento\App\Block\Factory;

use Tobento\Service\Repository\RepositoryInterface;

class Articles extends AbstractRepository
{
    public function type(): string
    {
        return 'articles';
    }

    public function viewName(): string
    {
        return 'block/articles';
    }

    protected function itemRepository(): string
    {
        return ArticleRepository::class;
    }

    protected function itemBaseWhere(): array
    {
        return ['status' => 'published'];
    }

    protected function itemsOrderByResolver(): null|callable
    {
        return function (string $sortBy): array {
            return match ($sortBy) {
                'title' => ['title' => 'asc'],
                'date' => ['date_created' => 'desc'],
                default => [],
            };
        };
    }

    protected function taxonomyRepository(): null|string
    {
        return CategoryRepository::class;
    }

    protected function taxonomyBaseWhere(): array
    {
        return [];
    }

    protected function taxonomyIdsResolver(): null|callable
    {
        return function (RepositoryInterface $taxonomyRepository, array $taxonomyIds): array {
            $categories = $taxonomyRepository->findAll(where: ['id' => ['in' => $taxonomyIds]]);

            $itemIds = [];

            foreach ($categories as $category) {
                $itemIds = [...$itemIds, ...$category->articleIds()];
            }

            return array_unique($itemIds);
        };
    }
}
```

**Notes**

- `viewName()` must return a dedicated view such as `block/articles`, `block/products`, or `block/events` - there is no generic repository fallback view, so every repository block needs its own theme template.
- `itemsOrderByResolver()` maps the raw `sortBy` value selected in the editor to a repository-compatible `orderBy` array. Returning `null` disables ordering entirely.
- `taxonomyIdsResolver()` converts the selected taxonomy IDs into a list of item IDs, which are merged into the item filter. Returning `null` disables taxonomy-based filtering entirely, even if a taxonomy repository is configured.
- If the resolved item repository does not implement `RepositoryInterface`, or is otherwise unavailable in the container, `createBlock()` returns a `NullBlock` instead of throwing, so a misconfigured block fails safely with no output.
- If the repository implements `LocalesAware`, the factory automatically switches it to the block's locale before querying, so items are always fetched in the correct language.
- `idName()` defaults to `'id'` and defines which field is used to filter by selected item IDs. Override it if your repository uses a different primary key name.

#### Rendering Block

Blocks created by `Tobento\App\Block\Factory\AbstractRepository` are rendered
using `Tobento\App\Block\Block\Repository`. Unlike `Fields` and `Items`, this
block does not deal with `FieldInterface` instances at all - it fetches raw
items from the repository at render time and passes them straight to the view.

```php
public function render(): string
{
    // resolving taxonomy ids, item id filtering, fetching and sorting items...

    return $this->view->render(view: $view, data: [
        'block' => $this,
        'items' => $items,
        'generateImagesInBackground' => $this->generateImagesInBackground,
    ]);
}
```

**How rendering works**

- If no item repository could be resolved, the fetched items list is simply empty, and the view still renders normally with an empty `$items`.
- If a taxonomy repository and resolver are configured and taxonomy IDs are selected, matching item IDs are resolved and merged with any explicitly selected item IDs.
- If any item IDs are present (explicit or taxonomy-resolved), they're added as an `in` where condition.
- Items are fetched via `itemRepository->findAll()`, respecting `itemBaseWhere`, the resolved `orderBy`, and `maxNumberOfItems` (capped at `1000`).
- The resolved items are passed to the view as `$items`, alongside `$block` and `$generateImagesInBackground`.

##### Example View

```php
<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-articles', 'cards']);
?>
<div<?= $attributes ?>>
    <?php foreach ($items as $item) { ?>
        <div class="card">
            <h3><?= $view->esc($item->title()) ?></h3>
            <p><?= $view->esc($item->excerpt()) ?></p>
        </div>
    <?php } ?>
</div>
```

Unlike `Fields` and `Items` blocks, there is no `renderField()` helper here
and no editable/default rendering distinction - repository blocks always
render the same way, since their content comes from the repository rather
than editable CRUD data.

##### Notes

- Repository blocks have no `NullField`/field-accessor concept; work with `$items` directly in the view as plain repository entities.
- Since items are fetched fresh on every render, repository blocks always reflect the current state of the underlying data - unlike `Fields` or `Items` blocks, which render whatever was stored at edit time.
- A repository block silently renders empty output rather than throwing when misconfigured, so verify your repository and taxonomy resolvers manually during development rather than relying on visible errors.

##### Configuring

In the [Block Config](https://github.com/tobento-ch/app-block/tree/2.x#block-config) you may configure the existing `default` editor or create new editors using the `EditorFactory::class`, the same way as any other block:

```php
use Tobento\App\Block\Editable;
use Tobento\App\Block\EditorInterface;
use Tobento\App\Block\Factory;
use Tobento\App\Block\Editor\EditorFactory;

'editors' => [
    'default' => static function (EditorFactory $factory): EditorInterface {
        $factory->addEditableBlocks([
            'articles' => Editable\Articles::class,
        ]);

        $factory->addBlockFactories([
            'articles' => Factory\Articles::class,
        ]);

        return $factory->createEditor(name: 'default');
    },
],
```

### Available Fields

Renderable field classes used by block factories to transform editable CRUD fields
into final block output. Each field corresponds to a class in `Tobento\App\Block\Field`
and implements `FieldInterface` (`render()` for default mode, `renderEditable()` for
editable mode).

- [Field\Data](#fielddata)
- [Field\File](#fieldfile)
- [Field\Files](#fieldfiles)
- [Field\Html](#fieldhtml)
- [Field\HtmlTextEditor](#fieldhtmltexteditor)
- [Field\Image](#fieldimage)
- [Field\ListField](#fieldlistfield)
- [Field\NullField](#fieldnullfield)
- [Field\Text](#fieldtext)

Fields are referenced by class in a factory's `configureFieldMapping()`, for example:

```php
namespace Tobento\App\Block\Factory;

use Tobento\App\Block\Field;
use Tobento\App\Block\FieldInterface;

class Hero extends AbstractFields
{
    public function type(): string
    {
        return 'hero';
    }

    protected function configureFieldMapping(): iterable
    {
        return [
            'data.image'  => Field\Image::class,
            'translation' => Field\HtmlTextEditor::class,
        ];
    }
}
```

#### `Field\Data`

Represents a structured data field used for configuration, lists, or option sets.  
Unlike text-based fields, `Field\Data` does not render visible output itself - instead, it provides **typed data** to your block view (arrays).

Use this field when your block needs **non-visual data**, such as:

- display options  
- configuration arrays  

```php
'display' => Field\Data::class,
'options'   => Field\Data::class,
```

**Notes**
- `Field\Data` does not produce HTML output. Rendering is entirely handled in your block view.
- The returned value depends is an `array`.
- Access the data field using `$block->fields()->data('name')`.

**Mapping CRUD Fields**

Only CRUD fields that return structured values (arrays) should be mapped to this block field.

Supported mappings:

- **[Field\Checkboxes](https://github.com/tobento-ch/app-crud#checkboxes-field)** - array of selected values  
- **[Field\Options](https://github.com/tobento-ch/app-crud#options-field)** - array of selected values  

**View Example**

```php
<?php
$fields = $block->fields();

// Access a data field (array-like object)
$display = $fields->data('display');

if ($display->contains('image')) {
    // User enabled the "Preview Image" option
}

// Access another data field
$content = $fields->data('content');

// Check if a nested key exists inside the data structure
if ($content->has('meta.title')) {
    // The nested key "meta.title" exists
}

// Access nested data with a fallback value
$title = $content->get('meta.title', 'fallback value');
```

#### `Field\File`

Renders a single downloadable file as a link. Use this in `configureFieldMapping()`
for a CRUD [`Field\File`](https://github.com/tobento-ch/app-crud#file-field) field that represents one file (e.g. a PDF attached
to a block).

```php
'data.attachment' => Field\File::class,
```

**Notes**

- `file()` returns the underlying normalized `Item` collection, giving custom views full control over how the file is rendered — the built-in `render()` only provides a minimal fallback link.
- `definition()` / `withDefinition()` let you override the picture or file definition used, and support named definitions via an array (e.g. multiple sizes or variants).
- If the file has no `src`, `render()` returns an empty string.
- The link text falls back to the file's `title`, or the filename itself if no title is set.
- `renderEditable()` is identical to `render()` - there is no separate editable markup for this field.

**Mapping CRUD Fields**

This field is designed to work with CRUD **File** fields that store file metadata (`storage`, `path`, etc.).  
The CRUD field does not map as a string. Instead, the File field reads the stored file information from `data.file`.

Supported source field:

- **[Field\File](https://github.com/tobento-ch/app-crud#file-field)** - provides the file data used by this renderable field

**Custom View Example**

For custom views you can access the underlying `Item` and its raw values directly,
instead of relying on the field's default `render()` output:

```php
<?php
$fileField = $block->fields()->get('data.attachment');
$file = $fileField->file();
$src = $file->get('src', '');

// you may get a definition if rendering a preview image
$definition = $fileField->definition(name: 'large');
?>
<?php if ($src) { ?>
    <a href="<?= $view->esc($view->routeUrl('media.file.download', [
        'storage' => $file->raw('storage', 'downloads'),
        'path' => $src,
    ])) ?>">
        <?= $view->esc($file->get('title', basename($src))) ?>
    </a>
<?php } ?>
```

#### `Field\Files`

Renders a list of downloadable files. Use this in `configureFieldMapping()` for a
CRUD [`Field\Files`](https://github.com/tobento-ch/app-crud#files-field) field that stores multiple files, such as the [Downloads Block](#downloads-block).

```php
'data.files' => Field\Files::class,
```

**Notes**

- `files()` returns the underlying normalized `Items` collection, for custom views that need more control than the built-in `<ul>` markup — this is how complex blocks like Downloads build their own layout.
- `definition()` reads the currently set picture/file definition; pass a `name` to look up a specific one when multiple named definitions were configured as an array, otherwise it falls back to `'block-field-files'`. Use this when a custom view needs to render a preview image alongside each file.
- `withDefinition()` overrides the definition used for rendering — note this replaces the definition entirely with a single value, so it's not for picking a named definition (use `definition(name: ...)` for that), but for swapping the default definition altogether from within a custom view.
- Each file without a `src` is silently skipped.
- The link text falls back to the file's `name`, or the filename itself if no name is set.
- `renderEditable()` is identical to `render()`.

**Mapping CRUD Fields**

This field is designed to work with CRUD **Files** fields that store multiple file
metadata entries (`storage`, `path`, `title`, etc.).  
The CRUD field does **not** map as a string. Instead, the Files field reads the stored file information from `data.files`.

Supported source field:

- **[Field\Files](https://github.com/tobento-ch/app-crud#files-field)** - provides the array of file metadata used by this renderable field

**Custom View Example**

For custom views you can iterate the underlying `Items` collection directly,
instead of relying on the field's default `<ul>` output:

```php
<?php
$filesField = $block->fields()->get('data.files');

// you may get a definition if rendering a preview image
$definition = $filesField->definition(name: 'large');
?>
<div class="block-files">
    <?php foreach ($filesField->files() as $file) { ?>
        <?php $src = $file->get('src', ''); ?>
        <?php if ($src) { ?>
            <a href="<?= $view->esc($view->routeUrl('media.file.download', [
                'storage' => $file->raw('storage', 'downloads'),
                'path' => $src,
            ])) ?>">
                <?= $view->esc($file->get('name', basename($src))) ?>
            </a>
        <?php } ?>
    <?php } ?>
</div>
```

#### `Field\Html`

Renders sanitized, editor-authored raw HTML. Use this for CRUD `Html` fields
where the editor writes markup directly rather than through a rich-text toolbar.

```php
'content' => Field\Html::class,
```

**Notes**

- The HTML is always passed through `$view->sanitizeHtml()` before output, both in `render()` and `renderEditable()`.
- Unlike `HtmlTextEditor`, this field has no JS editor wrapper - it renders the sanitized HTML directly in both modes.

**Mapping CRUD Fields**

Only CRUD fields that return a **string value** can be mapped to this block field.

Supported mappings:

- **[Field\Text](https://github.com/tobento-ch/app-crud#text-field)** - plain string  
- **[Field\Textarea](https://github.com/tobento-ch/app-crud#textarea-field)** - multi‑line string  
- **[Field\Html](https://github.com/tobento-ch/app-crud#html-field)** - HTML string  
- **[Field\SingleOptions](https://github.com/tobento-ch/app-crud#singleoptions-field)** - single option value  
- **[Field\Radios](https://github.com/tobento-ch/app-crud#radios-field)** - selected radio value  
- **[Field\Value](https://github.com/tobento-ch/app-crud#value-field)** - raw string value  
- **[Field\FileSource](https://github.com/tobento-ch/app-crud#filesource-field)** - plain string  

#### `Field\HtmlTextEditor`

Renders rich text produced by a JS-based text editor (the [Js Editor](https://github.com/tobento-ch/js-editor)),
used for blocks like Hero and Text. Use this whenever a field's content should
be authored through formatting tools rather than raw HTML or plain text.

```php
'translation' => Field\HtmlTextEditor::class,
```

**Notes**

- `render()` returns the sanitized HTML for default mode output.
- `renderEditable()` wraps the sanitized HTML in a `data-editor`-attributed `<div>`, which is what activates the live JS editor in the browser. This is the mechanism behind blocks like Hero's inline text editing.
- For item-list blocks, `itemIndex` adds a `data-editor-item` attribute so the JS editor can target the correct item.
- `withToolbar()` restricts which formatting tools are shown. An empty toolbar means all tools are enabled.

**Mapping CRUD Fields**

Only CRUD fields that return a **string value** can be mapped to this block field.

Supported mappings:

- **[Field\Text](https://github.com/tobento-ch/app-crud#text-field)** - plain string  
- **[Field\Textarea](https://github.com/tobento-ch/app-crud#textarea-field)** - multi-line string  

#### `Field\Image`

Renders a responsive `<picture>` element via the [Picture Feature](https://github.com/tobento-ch/app-media#picture-feature),
optionally wrapped in a `<figure>` with a caption.  
Use this for CRUD `File` fields that hold an image, such as `data.image` on the Hero block.

```php
'data.image' => Field\Image::class,
```

**Notes**

- `withDefinition()` overrides which [picture definition](https://github.com/tobento-ch/app-media#picture-feature) is used to generate the responsive image variants.
- If an `imgAlt` value is set, it's applied as the `<img>` element's `alt` attribute.
- If `imgWidth` is `50` or greater, the image is resized to that width, and its `height` is recalculated proportionally from the original dimensions when available.
- If a `figcaption` is set, the output is wrapped in `<figure><figcaption>` with `withFigureAttributes()` controlling the figure's own attributes; without a caption, only the `<picture>` markup is returned.
- `generateImagesInBackground` controls whether missing image variants are generated synchronously or queued, the same setting used by [Background Image Generation](#background-image-generation).
- `renderEditable()` is identical to `render()` - the image itself isn't edited inline. File replacement happens through the CRUD file field, not this renderable field.

**Mapping CRUD Fields**

This field is designed to work with CRUD **File** fields that store image metadata (`storage`, `path`, etc.).  
The CRUD field does not map as a string. Instead, the Image field reads the stored file information from `data.image`.

Supported source field:

- **[Field\File](https://github.com/tobento-ch/app-crud#file-field)** - provides the image file data used by this renderable field

#### `Field\ListField`

Renders a simple unordered list (`<ul>`) from an array of scalar values.  
Use this for CRUD fields that store a flat list of strings, such as tags or bullet points.

```php
'data.tags' => Field\ListField::class,
```

**Notes**

- Each item is normalized via `ensureString()`: strings and `Stringable` values pass through unchanged, scalars and booleans are cast to string, `null` becomes an empty string, and arrays/objects fall back to JSON encoding.
- Escaping happens in `render()`, not in `ensureString()`, so normalized `Stringable`/`Htmlable` values are still escaped correctly through `$view->esc()`.
- If the underlying array is empty, `render()` returns an empty string rather than an empty `<ul>`.
- `renderEditable()` is identical to `render()`.

**Mapping CRUD Fields**

Only CRUD fields that return an **array value** can be mapped to this block field.

Supported mappings:

- **[Field\Checkboxes](https://github.com/tobento-ch/app-crud#checkboxes-field)** - returns an array of selected values  
- **[Field\Options](https://github.com/tobento-ch/app-crud#options-field)** - returns an array of selected option values  

#### `Field\NullField`

A non-rendering placeholder field. Always returns an empty string from both
`render()` and `renderEditable()`. This is what `$fields->get($name)` and item
accessors return when the requested field doesn't exist, so views calling
`renderField()` on a missing field safely produce no output instead of an error.

```php
$fields->get('nonexistent'); // returns a NullField instance
```

**Notes**

- You generally don't reference `Field\NullField::class` in `configureFieldMapping()` yourself - it's returned automatically by the [Fields Accessor](#fields-accessor) and [Items Accessor](#items-accessor) as a safe fallback for missing fields.

#### `Field\Text`

Renders plain, HTML-escaped text. Use this for simple CRUD `Text` fields where
no formatting or markup should be allowed, such as a title, label, or short
caption.

```php
'question' => Field\Text::class,
```

**Notes**

- The text is always passed through `$view->esc()`, so any HTML in the stored value is rendered as literal text rather than markup.
- `renderEditable()` is identical to `render()` — there is no separate editable markup for this field, since plain text is edited entirely through the CRUD form field, not inline.

**Mapping CRUD Fields**

Only CRUD fields that return a **string value** can be mapped to this block field.

Supported mappings:

- **[Field\Text](https://github.com/tobento-ch/app-crud#text-field)** - plain string  
- **[Field\Textarea](https://github.com/tobento-ch/app-crud#textarea-field)** - multi‑line string  
- **[Field\SingleOptions](https://github.com/tobento-ch/app-crud#singleoptions-field)** - single option value  
- **[Field\Radios](https://github.com/tobento-ch/app-crud#radios-field)** - selected radio value  
- **[Field\Value](https://github.com/tobento-ch/app-crud#value-field)** - raw string value  
- **[Field\FileSource](https://github.com/tobento-ch/app-crud#filesource-field)** - plain string  

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
     * Configure reorder block.
     *
     * Called before a block's sortorder is updated.
     * Allows configurators to validate or deny reorder operations.
     *
     * @param BlockEntityInterface $entity
     * @return BlockEntityInterface
     * @throws HttpException
     */
    public function configureReorderBlock(BlockEntityInterface $entity): BlockEntityInterface
    {
        return $entity;
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

If you want to apply configurator logic **based on block ownership**, see  
**[Owner Configurator](#owner-configurator)** for owner-aware configurators and ownership delegation.

## Available Configurators

### Null Configurator

A configurator that performs no changes.  
Useful as a default or fallback configurator.

```php
use Tobento\App\Block\NullConfigurator;

class NullConfigurator implements ConfiguratorInterface
{
    public function configureEditableBlocks(string $for, EditableBlocksInterface $blocks, array $options): EditableBlocksInterface
    {
        return $blocks;
    }

    public function configureEditableBlockButtons(array $buttons, BlockEntityInterface $entity): array
    {
        return $buttons;
    }

    public function configureActionFields(ActionInterface $action, FieldsInterface $fields): FieldsInterface
    {
        return $fields;
    }

    public function configureReorderBlock(BlockEntityInterface $entity): BlockEntityInterface
    {
        return $entity;
    }
    
    public function configureCreateBlock(array $block): array
    {
        return $block;
    }

    public function configureCreateBlockFromEntity(BlockEntityInterface $entity): BlockEntityInterface
    {
        return $entity;
    }
}
```

### Owner Configurator

Owner-aware configurators allow you to apply configurator logic **only** to
blocks that belong to a specific owner. They work through a **delegation
composite** called `OwnerConfigurator`, which forwards configuration calls
to the **first configurator that claims ownership** of a block entity.

#### Delegating OwnerConfigurator (composite)

```php
use Tobento\App\Block\ConfiguratorInterface;
use Tobento\App\Block\OwnerConfiguratorInterface;
use Tobento\App\Block\BlockEntity;
use Tobento\App\Crud\Action\ActionInterface;
use Tobento\App\Crud\Field\FieldsInterface;
use Tobento\App\Http\Exception\HttpException;

class OwnerConfigurator implements ConfiguratorInterface
{
    protected array $configurators = [];

    public function registerConfigurator(ConfiguratorInterface $configurator): static
    {
        $this->configurators[] = $configurator;
        return $this;
    }

    public function configureActionFields(ActionInterface $action, FieldsInterface $fields): FieldsInterface
    {
        $entity = new BlockEntity($action->entity()->toArray());

        foreach ($this->configurators as $configurator) {
            if ($configurator instanceof OwnerConfiguratorInterface && $configurator->owns($entity)) {
                return $configurator->configureActionFields($action, $fields);
            }
        }

        // Deny by default: no registered configurator claimed this entity.
        throw new HttpException(403, trans('Access denied.'));
    }

    // Other configure* methods delegate in the same way...
}
```

Only the configurator whose `owns()` method returns `true` handles the
block. If none does, the block is rejected outright - there is no
permissive fallback.

#### OwnerConfiguratorInterface

```php
use Tobento\App\Block\OwnerConfiguratorInterface;

interface OwnerConfiguratorInterface
{
    /**
     * Returns true if the configurator owns the block entity.
     */
    public function owns(BlockEntityInterface $entity): bool;
}
```

This enables:

- filtering blocks by owner
- enforcing ACL rules
- restricting block types per owner
- isolating blocks between different CRUD resources

#### Two distinct ownership signals

There are **two distinct ownership signals**, each serving a different
integration scenario.

##### 1. Dynamic / resource-resolved ownership

Used by the [Block Views Editor Middleware](https://github.com/tobento-ch/app-block#block-views-editor-middleware)
integration. Applies to blocks rendered **inline on a resource page** -
articles, products, categories, any resource resolved per request. The
owning resource is **not knowable statically**; the same integration point
may serve an article on one request and a product on the next. Ownership is
resolved **per request** using `resource_id` (e.g. `"articles:123"`) or
`position` (for non-resource blocks like header/footer).

```php
public function owns(BlockEntityInterface $entity): bool
{
    if ($entity->editor() !== 'default') {
        return false;
    }
    $resourceIdRaw = $entity->resourceId();
    [$resourceKey, ] = array_pad(explode(':', $resourceIdRaw), 2, null);
    return $resourceKey === $this->resourceKey;
}
```

```php
public function owns(BlockEntityInterface $entity): bool
{
    if ($entity->editor() !== 'default') {
        return false;
    }
    $resourceIdRaw = $entity->resourceId();
    if (! empty($resourceIdRaw)) {
        return false; // resource-bound, not ours
    }
    $position = $entity->get('position', '');
    return $position !== '' && $position !== 'resource';
}
```

##### 2. Static / declared ownership

Used by the [Crud Editor Field](https://github.com/tobento-ch/app-block#crud-editor-field)
integration. The owner is **known at the moment the field is declared** -
fixed, predictable, independent of routing or slugs.

```php
yield new BlockEditor(name: 'blocks', label: 'Blocks')
    ->editor(name: 'mail', owner: 'newsletter');
```

```php
public function owns(BlockEntityInterface $entity): bool
{
    if ($entity->editor() !== 'mail') {
        return false;
    }
    if (! empty($entity->resourceId())) {
        return false; // never claim resource-bound blocks, regardless of owner()
    }
    return $entity->owner() === 'newsletter';
}
```

#### Rule of thumb

- If the owner can **only be known per request** (resolved via routing,
  slug, `resource_id`, or dynamic position): use **dynamic ownership**.
- If the owner is **fixed at the point the field is declared in code**: use
  **static ownership**.

Do not force one signal to answer the other's question - both mechanisms
coexist and serve different integration layers.

#### Guard against ambiguous or conflicting signals

Block entities are ultimately populated from client-submitted data. A
malformed or tampered payload could in principle carry both a `resource_id`
**and** an `owner` on the same entity - e.g. a request crafted against a
static editor's endpoint that also injects a `resource_id`.

**Every `owns()` implementation must check `editor()` as a baseline scope**,
in addition to whichever signal (resource-based or owner-based) it actually
uses. This is what prevents cross-editor leakage - not whether editors
happen to share one `OwnerConfigurator` instance or use separate ones.
Static owner configurators must additionally reject any entity carrying a
`resource_id`, regardless of what `owner()` reports.

**Return `false`, never throw, from inside `owns()`.** The composite's
existing fail-closed default already rejects any entity nothing claims -
reuse that single rejection path rather than duplicating exception-throwing
logic across every configurator. Throwing from within `owns()` also risks
firing before the *correct* configurator gets a chance to evaluate the
entity, since configurators are checked in registration order.

Sharing a single `OwnerConfigurator` instance across multiple editors is a
reasonable simplicity convention (one place to review the full list of
registered configurators), but it is **not**, by itself, what makes
ownership resolution safe - the `editor()` scope check inside each
configurator is the actual safeguard, and is required whether or not
editors share one composite instance.

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