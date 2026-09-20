<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-items', 'cards', 'cards-small']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="card">
            <div class="card-body">
                <?php foreach ($item->all() as $field) { ?>
                    <div class="block-items-field">
                        <?= $block->renderField($field) ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>