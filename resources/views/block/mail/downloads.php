<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-downloads']);
?>
<div<?= $attributes ?>>
    <?php foreach($files as $file) { ?>
        <?php
        $storage = $file['storage'] ?? 'downloads';
        $src = $file['src'] ?? '';
        $f = $view->fileStorage(storage: $storage)->with('stream', 'mimeType')->file(path: $src);
        ?>
        <div class="mb-m">
            <div class="mb-xs">
            <?php if (!empty($file['image'])) { ?>
                <?= $view->picture(
                    path: $file['image'],
                    resource: 'uploads',
                    definition: $pictureDefinition,
                    queue: $generateImagesInBackground,
                )->imgAttr('alt', $file['name'][$locale] ?? $f->name())->imgAttr('loading', 'lazy') ?>
            <?php } ?>
            </div>
            <div class="text-s">
                <?php if (!empty($file['name'][$locale])) { ?>
                    <div class="text-l mb-xs"><?= $view->esc($file['name'][$locale]) ?></div>
                <?php } else { ?>
                    <div class="text-l mb-xs"><?= $view->esc($f->name()) ?></div>
                <?php } ?>
                <div class="mb-xs"><?= $view->etrans('Size') ?>: <?= $view->esc($f->humanSize()) ?></div>
                <div class="mb-xs"><?= $view->etrans('Format') ?>: <?= $view->esc($f->extension()) ?></div>
                <div class="mb-xs"><a href="<?= $view->esc($view->routeUrl('media.file.download', ['storage' => $storage, 'path' => $src])) ?>" class="button"><?= $view->etrans('Download') ?></a></div>
                <div class="mb-xs"><a href="<?= $view->esc($view->routeUrl('media.file.display', ['storage' => $storage, 'path' => $src])) ?>" class="button" target="_blank"><?= $view->etrans('View In Browser') ?></a></div>
            </div>
        </div>
    <?php } ?>
</div>