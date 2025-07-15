<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-hero']);
?>
<div<?= $attributes ?>>
    <div class="hero-body">
        <div class="content" data-editor><?= $view->sanitizeHtml($block->html()) ?></div>
    </div>
    <div class="hero-media"><?= $pictureTag ?></div>
</div>