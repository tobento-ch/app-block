<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-faq']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="mb-m">
            <div class="faq-question title text-s mb-xxs">
                <?= $block->renderField($item->get('question')) ?>
            </div>
            <div class="faq-answer text-body mb-m">
                <?= $block->renderField($item->get('answer')) ?>
            </div>
        </div>
    <?php } ?>
</div>