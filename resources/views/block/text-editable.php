<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-text', 'content']);
?>
<div<?= $attributes ?>>
    <div data-editor><?= $view->sanitizeHtml($block->html()) ?></div>
</div>