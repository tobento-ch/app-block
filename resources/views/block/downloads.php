<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-downloads', 'cards']);
?>
<?php if ($files->count() > 0) { ?>
    <div<?= $attributes ?>>
        <?php foreach($files as $file) { ?>
            <?php
            $storage = $file->raw(name: 'storage', default: 'downloads');
            $src = $file->get('src', '');
            $f = $view->fileStorage(storage: $storage)->with('stream', 'mimeType')->file(path: $src);
            ?>
            <?php if ($src) { ?>
            <div class="card">
                <div class="card-body">
                <?php if (!empty($file->raw('image'))) { ?>
                    <div class="max-width-s"><?= $view->picture(
                        path: $file->raw('image'),
                        resource: 'uploads-public',
                        definition: $pictureDefinition,
                        queue: $generateImagesInBackground,
                    )->imgAttr('alt', $file->get(name: 'name', default: $f->name()))->imgAttr('loading', 'lazy') ?></div>
                <?php } ?>
                </div>
                <div class="card-foot overflow-wrap-anywhere">
                    <ul class="unstyled text-xs">
                        <?php if ($file->has(name: 'name')) { ?>
                            <li class="text-m"><?= $view->esc($file->get(name: 'name', default: '')) ?></li>
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
        <?php } ?>
    </div>
<?php } ?>