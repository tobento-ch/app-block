<?php
use Tobento\Service\Tag\Attributes;

$view->asset('assets/block/block-editor.css');
$view->asset('assets/block/blocks.css');
$view->asset('assets/block/block-editors.js')->attr('type', 'module');
$view->asset('assets/modal/modals.css');

$view->asset('assets/crud/field-items.js')->attr('type', 'module');
$view->asset('assets/crud/field-file.js')->attr('type', 'module');
$view->asset('assets/crud/field-file-source.js')->attr('type', 'module');

$view->asset('assets/media/image-editors.css');
$view->asset('assets/js-cropper/cropper.css');
$view->asset('assets/media/image-crop.js')->attr('type', 'module');
$view->asset('assets/media/image-editors.js')->attr('type', 'module');
$view->asset('assets/media/picture-editors.js')->attr('type', 'module');

$view->asset('assets/js-notifier/notifier.css');

$view->asset('assets/block/crud-field-options.js')->attr('type', 'module');
$view->asset('assets/block/crud-field-single-options.js')->attr('type', 'module');
$view->asset('assets/block/block-text.js')->attr('type', 'module');
$view->asset('assets/js-editor/editor.js')->attr('type', 'module');
$view->asset('assets/js-editor/editor.css');

$attributes = new Attributes([
    'class' => 'block-editor'.($options['displayAsTextarea'] ? ' textarea' : ''),
    'data-block-editor' => [
        'name' => $editorName,
        'id' => $editorId,
        'storeUrl' => (string)$view->routeUrl('block-editor.store.block'),
        'editUrl' => (string)$view->routeUrl('block-editor.edit.block'),
        'updateUrl' => (string)$view->routeUrl('block-editor.update.block'),
        'deleteUrl' => (string)$view->routeUrl('block-editor.delete.block'),
        'reorderUrl' => (string)$view->routeUrl('block-editor.reorder.blocks'),
        'storeBlocksToInput' => $options['storeBlocksToInput'] ?? null,
    ]
]);
?>
<div<?= $attributes ?>>
    <?php foreach($blocks as $block) { ?>
        <?= $block->render() ?>
    <?php } ?>
    <div class="new-block" data-block-editor-section="new">
        <span class="link" data-block-action="new"><?= $view->etrans('Add Block') ?></span>
    </div>
</div>
<?php if (isset($options['storeBlocksToInput']) && is_string($options['storeBlocksToInput'])) { ?>
    <input name="<?= $view->esc($options['storeBlocksToInput']) ?>" value="" type="hidden">
<?php } ?>

<div class="modal modal-block top right" data-modal='{"id": "block-editor-<?= $view->esc($editorId) ?>"}'>
    <div class="modal-content modal-m">
        <div class="modal-body"><!-- --></div>
        <div class="modal-foot">
            <div class="buttons spaced">
                <span class="link modal-close"><?= $view->etrans('close') ?></span>
            </div>
        </div>
    </div>
</div>
<div class="modal modal-block top right" data-modal='{"id": "block-editor-blocks-<?= $view->esc($editorId) ?>"}'>
    <div class="modal-content modal-m">
        <div class="modal-head">
            <input name="blocks_search" type="search" class="small fit" placeholder="<?= $view->etrans('Search for blocks') ?>">
        </div>
        <div class="modal-body">
            <?php
            $editableBlocks = $configurator->configureEditableBlocks(
                for: 'new',
                blocks: $editableBlocks->sort(),
                options: $options
            );
    
            foreach ($editableBlocks as $editableBlock) {

                $blockAttributes = new Attributes([
                    'class' => 'mb-s',
                    'data-block-action' => 'add',
                    'data-block' => array_merge(
                        $editableBlock->defaultBlock(),
                        [
                            'status' => $options['status'] ?? 'pending',
                            'resource_id' => $options['resource_id'] ?? null,
                            'resource_group' => $options['resource_group'] ?? null,
                            'position' => $options['position'] ?? null,
                            'owner' => $options['owner'] ?? null,
                        ]
                    ),
                ]);
                ?>
                <div<?= $blockAttributes ?>>
                    <div class="link">
                        <span class="icon">
                            <?= $editableBlock->icon() ?>
                            <span class="text-s" data-block-search=""><?= $view->esc($editableBlock->title()) ?></span>
                        </span>
                    </div>
                    <?php if ($editableBlock->description()) { ?>
                        <div class="text-xxs"><?= $view->esc($editableBlock->description()) ?></div>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
        <div class="modal-foot">
            <div class="buttons spaced">
                <span class="link modal-close"><?= $view->etrans('cancel') ?></span>
            </div>
        </div>
    </div>
</div>