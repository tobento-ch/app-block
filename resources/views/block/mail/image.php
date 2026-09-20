<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-image']);
$fields = $block->fields();
$image = $fields->get('data.image');
//$image->withDefinition('block-image-fit');
?>
<div<?= $attributes ?>><?= $block->renderField($image) ?></div>