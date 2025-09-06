<?php
use Tobento\Service\Tag\Attributes;

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
        <?php
        foreach($positions as $i => $position) {
            $blocks = $blocksByPosition[$position] ?? [];
            echo '<div>'.$view->esc($position).'</div>';
            echo '<div'.(new Attributes($attributes))->add('class', 'content mt-xs mb-m').'>';
            echo $editor->render(
                id: $field->name().':'.$position,
                blocks: $blocks,
                options: [
                    'resource_id' => $field->getResourceId(),
                    'resource_group' => $field->getResourceGroup(),
                    'position' => $position,
                    'storeBlocksToInput' => $form->nameToArray($field->name().'.'.$i),
                    'displayAsTextarea' => true,
                ],
            );
            echo '</div>';
        } ?>
        <?php if ($field->getInfoText(action: $actionName)) { ?>
            <p class="text-xxs"><?= $view->esc($field->getInfoText(action: $actionName)) ?></p>
        <?php } ?>
    </div>
</div>