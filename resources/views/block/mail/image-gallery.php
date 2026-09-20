<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-image-gallery']);

$fields = $block->fields();
$filesField = $fields->get('data.images');
$images = $filesField->files();
?>
<div<?= $attributes ?>>
    <?php foreach($images as $image) { ?>
        <div class="image mb-xl">
            <?php $pictureTag = $view->picture(
                path: $image->get('src', ''),
                resource: $image->raw(name: 'storage', default: ''),
                definition: $filesField->definition(name: 'thumbnail'),
                queue: $generateImagesInBackground,
            )->imgAttr('alt', $image->get('alt.'.$locale, '')) ?>
            <?php if (!empty($image->get('figcaption.'.$locale, ''))) { ?>            
                <figure>
                    <?= $pictureTag ?>
                    <figcaption class="text-m mt-xs"><?= $view->esc($image->get('figcaption.'.$locale, '')) ?></figcaption>
                </figure>
            <?php } else { ?>
                <?= $pictureTag ?>
            <?php } ?>
        </div>
    <?php } ?>
</div>