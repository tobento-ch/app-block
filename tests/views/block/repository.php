<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-repository']);
?>
<div<?= $attributes ?>>
    <?php foreach ($items as $item) { ?>
        <div class="item"><?= $view->esc($item->get('title')) ?></div>
    <?php } ?>
</div>