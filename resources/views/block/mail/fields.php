<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-fields']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->fields()->all() as $field) { ?>
        <div class="mb-m"><?= $block->renderField($field) ?></div>
    <?php } ?>
</div>