<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-hero-mail']);
?>
<div<?= $attributes ?>>
    <div class="content" data-editor><?= $view->sanitizeHtml($block->html()) ?></div>
    <div class="mt-s"><?= $pictureTag ?></div>
</div>