<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-downloads', 'cards']);
?>
<div<?= $attributes ?>>
    <?php foreach($files as $file) { ?>
        <?php
        $storage = $file['storage'] ?? 'downloads';
        $src = $file['src'] ?? '';
        $f = $view->fileStorage(storage: $storage)->with('stream', 'mimeType')->file(path: $src);
        ?>
        <div class="card">
            <div class="card-body">
            <?php if (!empty($file['image'])) { ?>
                <div class="max-width-s"><?= $view->picture(
                    path: $file['image'],
                    resource: 'uploads',
                    definition: $pictureDefinition,
                    queue: $generateImagesInBackground,
                )->imgAttr('alt', $file['name'][$locale] ?? $f->name())->imgAttr('loading', 'lazy') ?></div>
            <?php } ?>
            </div>
            <div class="card-foot overflow-wrap-anywhere">
                <ul class="unstyled text-xs">
                    <?php if (!empty($file['name'][$locale])) { ?>
                        <li class="text-m"><?= $view->esc($file['name'][$locale]) ?></li>
                    <?php } else { ?>
                        <li class="text-m"><?= $view->esc($f->name()) ?></li>
                    <?php } ?>
                    <li><?= $view->etrans('Size') ?>: <?= $view->esc($f->humanSize()) ?></li>
                    <li><?= $view->etrans('Format') ?>: <?= $view->esc($f->extension()) ?></li>
                    <li class="buttons spaced mt-xs">
                        <a href="<?= $view->esc($view->routeUrl('media.file.download', ['storage' => $storage, 'path' => $src])) ?>" class="button"><?= $view->etrans('Download') ?></a>
                        <a href="<?= $view->esc($view->routeUrl('media.file.display', ['storage' => $storage, 'path' => $src])) ?>" class="button" target="_blank"><?= $view->etrans('View In Browser') ?></a>
                    </li>
                </ul>
            </div>
        </div>
    <?php } ?>
</div>