<?php
use Tobento\Service\Table\TableRenderer;

$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-downloads', 'content']);
$fields = $block->fields();
$filesField = $fields->get('data.files');
$files = $filesField->files();
$display = $fields->data('data.display');
$table = $view->table('downloads')->withRenderer(new TableRenderer());

$hasAnyImage = false;

foreach ($files as $file) {
    if (!empty($file->raw('image'))) {
        $hasAnyImage = true;
        break;
    }
}

$table->rows($files->all(), function($row, $file) use ($view, $filesField, $generateImagesInBackground, $display, $hasAnyImage): void {

    $storage = $file->raw('storage', 'downloads');
    $src = $file->get('src', '');
    $f = $view->fileStorage(storage: $storage)->with('stream', 'mimeType')->file(path: $src);

    if (empty($src)) {
        return;
    }

    // IMAGE COLUMN
    if ($display->contains('image') && $hasAnyImage) {

        if (!empty($file->raw('image'))) {

            $picture = $view->picture(
                path: $file->raw('image'),
                resource: 'uploads-public',
                definition: $filesField->definition(),
                queue: $generateImagesInBackground,
            )
                ->imgAttr('class', 'min-width-xxs max-width-xxs')
                ->imgAttr('alt', $file->get('name', $f->name()))
                ->imgAttr('loading', 'lazy');

            $row->column('picture', $picture);
        } else {
            $row->column('picture', '');
        }
    }

    // NAME COLUMN
    if ($display->contains('name')) {
        $row->column('name', $file->get('name', $f->name()));
    }

    // FILENAME COLUMN
    if ($display->contains('filename')) {
        $row->column('filename', $f->name());
    }

    // FORMAT COLUMN
    if ($display->contains('format')) {
        $row->column('format', $f->extension());
    }

    // SIZE COLUMN
    if ($display->contains('size')) {
        $row->column('size', $f->humanSize());
    }

    // DOWNLOAD BUTTON
    if ($display->contains('download')) {
        $row->column(
            'download',
            '<a href="'.$view->esc($view->routeUrl('media.file.download', ['storage' => $storage, 'path' => $src])).'" class="button raw">'.$view->etrans('Download').'</a>'
        );
    }

    // VIEW BUTTON
    if ($display->contains('view')) {
        $row->column(
            'view',
            '<a href="'.$view->esc($view->routeUrl('media.file.display', ['storage' => $storage, 'path' => $src])).'" class="button raw" target="_blank">'.$view->etrans('View in Browser').'</a>'
        );
    }

    // HTML columns
    $row->html('picture', 'download', 'view');
});
?>
<div<?= $attributes ?>><?= $table ?></div>