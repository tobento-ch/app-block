<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-image-gallery']);
?>
<div<?= $attributes ?>>
    <?php foreach($images as $image) { ?>
        <div class="image mb-xl">
            <?php $pictureTag = $view->picture(
                path: $image['src'] ?? '',
                resource: $image['storage'] ?? '',
                definition: $pictureDefinitionThumbnail,
                queue: $generateImagesInBackground,
            )->imgAttr('alt', $image['alt'][$locale] ?? '') ?>
            <?php if (!empty($image['figcaption'][$locale])) { ?>
                <figure>
                    <?= $pictureTag ?>
                    <figcaption class="text-m mt-xs"><?= $view->esc($image['figcaption'][$locale]) ?></figcaption>
                </figure>
            <?php } else { ?>
                <?= $pictureTag ?>
            <?php } ?>
        </div>
    <?php } ?>
</div>