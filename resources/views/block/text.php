<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-text', 'content']);
?>
<div<?= $attributes ?>><?= $view->sanitizeHtml($block->html()) ?></div>