<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-persons']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="mb-m">
            <div class="mb-xs">
                <?php if ($item->has('image')) { ?>
                    <?= $view->picture(
                        path: $item->get('image')->value(),
                        resource: 'uploads-public',
                        definition: 'block-persons',
                        queue: $generateImagesInBackground,
                    )
                    ->imgAttr('alt', $item->get('name')->value() ?? $item->get('image')->value()) ?>
                <?php } ?>
            </div>
            <div class="text-s">
                <?php if ($item->has('name')) { ?>
                    <div class="mb-xs text-l">
                        <?= $block->renderField($item->get('name')) ?>
                    </div>
                <?php } ?>

                <?php if ($item->has('position')) { ?>
                    <div class="mb-xs">
                        <?= $block->renderField($item->get('position')) ?>
                    </div>
                <?php } ?>

                <?php if ($item->has('email')) { ?>
                    <div class="mb-xs">
                        <a href="mailto:<?= $block->renderField($item->get('email')) ?>">
                            <?= $block->renderField($item->get('email')) ?>
                        </a>
                    </div>
                <?php } ?>

                <?php if ($item->has('tel')) { ?>
                    <div class="mb-xs">
                        <a href="tel:<?= $block->renderField($item->get('tel')) ?>">
                            <?= $block->renderField($item->get('tel')) ?>
                        </a>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>