<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-downloads', 'cards']);
$fields = $block->fields();
$filesField = $fields->get('data.files');
$files = $filesField->files();
$display = $fields->data('data.display');
?>
<?php if ($files->count() > 0) { ?>
    <div<?= $attributes ?>>
        <?php foreach($files as $file) { ?>
            <?php
            $storage = $file->raw('storage', 'downloads');
            $src = $file->get('src', '');
            $f = $view->fileStorage(storage: $storage)
                     ->with('stream', 'mimeType')
                     ->file(path: $src);
            ?>

            <?php if ($src) { ?>
            <div class="card">
                <div class="card-body">

                <?php if ($display->contains('image') && !empty($file->raw('image'))) { ?>
                    <div class="max-width-s mb-xs">
                        <?= $view->picture(
                            path: $file->raw('image'),
                            resource: 'uploads-public',
                            definition: $filesField->definition(),
                            queue: $generateImagesInBackground,
                        )
                        ->imgAttr('alt', $file->get('name', $f->name()))
                        ->imgAttr('loading', 'lazy') ?>
                    </div>
                <?php } ?>

                </div>

                <div class="card-foot overflow-wrap-anywhere">
                    <ul class="unstyled text-xs">
                        <?php if ($display->contains('name')) { ?>
                            <?php if ($file->has('name')) { ?>
                                <li class="text-m"><?= $view->esc($file->get('name', '')) ?></li>
                            <?php } else { ?>
                                <li class="text-m"><?= $view->esc($f->name()) ?></li>
                            <?php } ?>
                        <?php } ?>
                        
                        <?php if ($display->contains('filename')) { ?>
                            <li class="text-m"><?= $view->esc($f->name()) ?></li>
                        <?php } ?>
                        
                        <?php if ($display->contains('size')) { ?>
                            <li class="mt-s">
                                <?= $view->etrans('Size') ?>: <?= $view->esc($f->humanSize()) ?>
                            </li>
                        <?php } ?>

                        <?php if ($display->contains('format')) { ?>
                            <li>
                                <?= $view->etrans('Format') ?>: <?= $view->esc($f->extension()) ?>
                            </li>
                        <?php } ?>

                        <li class="buttons spaced mt-xs">
                            <?php if ($display->contains('download')) { ?>
                                <a href="<?= $view->esc($view->routeUrl('media.file.download', ['storage' => $storage, 'path' => $src])) ?>"
                                   class="button">
                                   <?= $view->etrans('Download') ?>
                                </a>
                            <?php } ?>

                            <?php if ($display->contains('view')) { ?>
                                <a href="<?= $view->esc($view->routeUrl('media.file.display', ['storage' => $storage, 'path' => $src])) ?>"
                                   class="button"
                                   target="_blank">
                                   <?= $view->etrans('View in Browser') ?>
                                </a>
                            <?php } ?>
                        </li>
                    </ul>
                </div>
            </div>
            <?php } ?>
        <?php } ?>
    </div>
<?php } ?>
