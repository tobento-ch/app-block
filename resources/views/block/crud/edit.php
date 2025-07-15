<?= $view->render('inc.messages') ?>
<?php $form = $view->form(); ?>
<?= $form->form([
    'action' => $action->getLinkUrl(),
    'enctype' => 'multipart/form-data',
    'method' => 'POST',
]) ?>
<div class="crud">
    <?php foreach($action->fields()->column('groupName', 'groupId') as $groupName) { ?>
        <section class="fields" data-fields-group="<?= $view->esc($groupName) ?>">
            <?php if ($groupName) { ?>
                <h2 class="group-title"><?= $view->esc($groupName) ?></h2>
            <?php } ?>
            <?php
            foreach($action->fields()->group($groupName) as $field) {
                echo $field->render();
            }
            ?>
        </section>
    <?php } ?>
</div>
<?= $form->close() ?>