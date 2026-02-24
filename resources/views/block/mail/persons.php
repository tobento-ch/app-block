<?php
$attributes = $block->options()->toTagAttributes();
$attributes->add('class', ['block', 'block-persons']);
?>
<div<?= $attributes ?>>
    <?php foreach($block->persons() as $person) { ?>
        <div class="mb-m">
            <div class="mb-xs">
            <?php if (!empty($person['image'])) { ?>
                <?= $view->picture(
                    path: $person['image'],
                    resource: 'uploads-public',
                    definition: $pictureDefinition,
                    queue: $generateImagesInBackground,
                )->imgAttr('alt', $person['name'] ?? $person['image']) ?>
            <?php } ?>
            </div>
            <div class="text-s">
                <?php if (!empty($person['name'])) { ?>
                    <div class="mb-xs text-l"><?= $view->esc($person['name']) ?></div>
                <?php } ?>
                <?php if (!empty($person['position'])) { ?>
                    <div class="mb-xs"><?= $view->esc($person['position']) ?></div>
                <?php } ?>
                <?php if (!empty($person['email'])) { ?>
                    <div class="mb-xs"><a href="mailto:<?= $view->esc($person['email']) ?>"><?= $view->esc($person['email']) ?></a></div>
                <?php } ?>
                <?php if (!empty($person['tel'])) { ?>
                    <div class="mb-xs"><a href="tel:<?= $view->esc($person['tel']) ?>"><?= $view->esc($person['tel']) ?></a></div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>