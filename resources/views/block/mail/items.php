<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-items']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="mb-m">
            <?php foreach ($item->all() as $field) { ?>
                <div class="mb-m">
                    <?= $block->renderField($field) ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>