<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-text', 'content']);
$fields = $block->fields();
?>
<div<?= $attributes ?>><?= $block->renderField($fields->get('translation')) ?></div>