<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-faq']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <details class="faq-item mb-xs"<?= $item->get('open')->value() === '1' ? ' open' : '' ?>>
            <summary class="faq-question link title text-s">
                <?= $block->renderField($item->get('question')) ?>
            </summary>
            <div class="faq-answer mt-xxs mb-m">
                <?= $block->renderField($item->get('answer')) ?>
            </div>
        </details>
    <?php } ?>
</div>