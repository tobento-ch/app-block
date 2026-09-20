<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-persons', 'cards', 'cards-small']);
?>
<div<?= $attributes ?>>
    <?php foreach ($block->items() as $item) { ?>
        <div class="card">
            <div class="card-body">
                <?php if ($item->has('image')) { ?>
                    <?= $view->picture(
                        path: $item->get('image')->value(),
                        resource: 'uploads-public',
                        definition: 'block-persons',
                        queue: $generateImagesInBackground,
                    )
                    ->imgAttr('alt', $item->get('name')->value() ?? $item->get('image')->value())
                    ->imgAttr('loading', 'lazy') ?>
                <?php } ?>
            </div>
            <div class="card-foot">
                <ul class="unstyled text-s">
                <?php if ($item->has('name')) { ?>
                    <li><?= $block->renderField($item->get('name')) ?></li>
                <?php } ?>
                <?php if ($item->has('position')) { ?>
                    <li><?= $block->renderField($item->get('position')) ?></li>
                <?php } ?>
                <?php if ($item->has('email')) { ?>
                    <li><a href="mailto:<?= $block->renderField($item->get('email')) ?>"><?= $block->renderField($item->get('email')) ?></a></li>
                <?php } ?>
                <?php if ($item->has('tel')) { ?>
                    <li><a href="tel:<?= $block->renderField($item->get('tel')) ?>"><?= $block->renderField($item->get('tel')) ?></a></li>
                <?php } ?>
                </ul>
            </div>
        </div>
    <?php } ?>
</div>