<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-hero', 'cols']);
?>
<div<?= $attributes ?>>
    <div class="col-6 content"><?= $view->sanitizeHtml($block->html()) ?></div>
    <div class="col-6"><?= $pictureTag ?></div>
</div>