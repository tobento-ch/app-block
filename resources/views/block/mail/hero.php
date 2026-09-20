<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-hero']);
$fields = $block->fields();
?>
<div<?= $attributes ?>>
    <div class="content"><?= $block->renderField($fields->get('translation')) ?></div>
    <div class="mt-s"><?= $block->renderField($fields->get('data.image')) ?></div>
</div>