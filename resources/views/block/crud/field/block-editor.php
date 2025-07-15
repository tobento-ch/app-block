<?php
$view->asset('assets/js-editor/editor.css');
$view->asset('assets/crud/field-text-editor.js')->attr('type', 'module');

$form = $view->form();
?>
<div class="field field-crud" data-field="<?= $view->esc($field->name()) ?>">
    <div class="field-label">
        <?= $form->label(
            text: $field->label(),
            for: null,
            requiredText: $field->getRequiredText(action: $actionName),
            optionalText: $field->getOptionalText(action: $actionName),
        ) ?>
    </div>
    <div class="field-body">
        <div class="content">
            <?= $editor->render(
                id: $field->name(),
                blocks: $blocks,
                options: [
                    'storeBlocksToInput' => $field->name(),
                    'displayAsTextarea' => true,
                ]
            ) ?>
        </div>
        <?php if ($field->getInfoText(action: $actionName)) { ?>
            <p class="text-xxs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
        <?php } ?>
    </div>
</div>