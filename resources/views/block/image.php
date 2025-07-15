<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-image']);
?>
<?php if ($figcaption) { ?>
    <div<?= $attributes ?>><figure><?= $pictureTag ?><figcaption><?= $view->esc($figcaption) ?></figcaption></figure></div>
<?php } else { ?>
    <div<?= $attributes ?>><?= $pictureTag ?></div>
<?php } ?>