<?php
$view->asset('assets/block/block-image-gallery.js')->attr('type', 'module');

$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-image-gallery', 'cards', 'cards-small']);
$attributes->add('data-image-gallery', '');
?>
<div<?= $attributes ?>>
    <?php foreach($images as $image) { ?>
        <div class="thumbnail cursor-zoom-in" data-gallery="open">
        <?= $view->picture(
            path: $image['src'] ?? '',
            resource: $image['storage'] ?? '',
            definition: $pictureDefinitionThumbnail,
            queue: $generateImagesInBackground,
        )->imgAttr('alt', $image['alt'][$locale] ?? '')->imgAttr('loading', 'lazy') ?>
        </div>
    <?php } ?>
    <template data-images="">
        <?php foreach($images as $image) { ?>
            <div class="image mb-xl" data-image="">
                <?php $pictureTag = $view->picture(
                    path: $image['src'] ?? '',
                    resource: $image['storage'] ?? '',
                    definition: $pictureDefinition,
                    queue: $generateImagesInBackground,
                )->imgAttr('alt', $image['alt'][$locale] ?? '')->imgAttr('loading', 'lazy') ?>
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
    </template>
</div>
<?php if ($view->once(__FILE__)) { ?>
    <div class="modal modal-block-image-gallery" data-modal='{"id": "block-image-gallery"}'>
        <div class="modal-background"></div>
        <div class="modal-content modal-l modal-tablet-full">
            <div class="modal-head">
                <div class="buttons spaced">
                    <span class="link modal-close"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-14z" /><path d="M9 9l6 6m0 -6l-6 6" /></svg></span>
                    <span class="link display-none-tablet" data-gallery="expand"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 16m0 1a1 1 0 0 1 1 -1h3a1 1 0 0 1 1 1v3a1 1 0 0 1 -1 1h-3a1 1 0 0 1 -1 -1z" /><path d="M4 12v-6a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-6" /><path d="M12 8h4v4" /><path d="M16 8l-5 5" /></svg></span>
                </div>
            </div>
            <div class="modal-body">
                <div class="max-width-xl m-0 m-auto" data-display="images"></div>
            </div>
        </div>
    </div>
<?php } ?>