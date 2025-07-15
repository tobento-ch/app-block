<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-persons', 'cards', 'cards-small']);
?>
<div<?= $attributes ?>>
    <?php foreach($block->persons() as $person) { ?>
        <div class="card">
            <div class="card-body">
            <?php if (!empty($person['image'])) { ?>
                <?= $view->picture(
                    path: $person['image'],
                    resource: 'uploads',
                    definition: $pictureDefinition,
                    queue: $generateImagesInBackground,
                )->imgAttr('alt', $person['name'] ?? $person['image'])->imgAttr('loading', 'lazy') ?>
            <?php } ?>
            </div>
            <div class="card-foot">
                <ul class="unstyled text-s">
                <?php if (!empty($person['name'])) { ?>
                    <li><?= $view->esc($person['name']) ?></li>
                <?php } ?>
                <?php if (!empty($person['position'])) { ?>
                    <li><?= $view->esc($person['position']) ?></li>
                <?php } ?>
                <?php if (!empty($person['email'])) { ?>
                    <li><a href="mailto:<?= $view->esc($person['email']) ?>"><?= $view->esc($person['email']) ?></a></li>
                <?php } ?>
                <?php if (!empty($person['tel'])) { ?>
                    <li><a href="tel:<?= $view->esc($person['tel']) ?>"><?= $view->esc($person['tel']) ?></a></li>
                <?php } ?>
                </ul>
            </div>
        </div>
    <?php } ?>
</div>