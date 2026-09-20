<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-downloads']);
$fields = $block->fields();
$filesField = $fields->get('data.files');
$files = $filesField->files();
$display = $fields->data('data.display');
?>
<?php if ($files->count() > 0) { ?>
    <div<?= $attributes ?>>
        <?php foreach ($files as $file) { ?>
            <?php
            $storage = $file->raw('storage', 'downloads');
            $src = $file->get('src', '');
            $f = $view->fileStorage(storage: $storage)
                     ->with('stream', 'mimeType')
                     ->file(path: $src);
            ?>
            <div class="mb-m">
                <div class="mb-xs">

                <?php if ($display->contains('image') && !empty($file->raw('image'))) { ?>
                    <?= $view->picture(
                        path: $file->raw('image'),
                        resource: 'uploads',
                        definition: $pictureDefinition,
                        queue: $generateImagesInBackground,
                    )
                    ->imgAttr('alt', $file->get('name', $f->name()))
                    ->imgAttr('loading', 'lazy') ?>
                <?php } ?>
                </div>
                <div class="text-s">

                    <?php if ($display->contains('name')) { ?>
                        <?php if ($file->has('name')) { ?>
                            <div class="text-l mb-xs"><?= $view->esc($file->get('name', '')) ?></div>
                        <?php } else { ?>
                            <div class="text-l mb-xs"><?= $view->esc($f->name()) ?></div>
                        <?php } ?>
                    <?php } ?>

                    <?php if ($display->contains('filename')) { ?>
                        <div class="text-xs mb-xs"><?= $view->esc($f->name()) ?></div>
                    <?php } ?>

                    <?php if ($display->contains('size')) { ?>
                        <div class="mb-xs">
                            <?= $view->etrans('Size') ?>: <?= $view->esc($f->humanSize()) ?>
                        </div>
                    <?php } ?>

                    <?php if ($display->contains('format')) { ?>
                        <div class="mb-xs">
                            <?= $view->etrans('Format') ?>: <?= $view->esc($f->extension()) ?>
                        </div>
                    <?php } ?>

                    <?php if ($display->contains('download')) { ?>
                        <div class="mb-xs">
                            <a href="<?= $view->esc($view->routeUrl('media.file.download', ['storage' => $storage, 'path' => $src])) ?>"
                               class="button">
                               <?= $view->etrans('Download') ?>
                            </a>
                        </div>
                    <?php } ?>

                    <?php if ($display->contains('view')) { ?>
                        <div class="mb-xs">
                            <a href="<?= $view->esc($view->routeUrl('media.file.display', ['storage' => $storage, 'path' => $src])) ?>"
                               class="button"
                               target="_blank">
                               <?= $view->etrans('View In Browser') ?>
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
<?php } ?>