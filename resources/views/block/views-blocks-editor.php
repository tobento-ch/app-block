<div class="blocks">
    <?php
    echo $editor->render(
        id: 'view.'.$position,
        blocks: $blocks,
        options: [
            'status' => 'active',
            'resource_id' => $resourceId,
            'resource_group' => $resourceGroup,
            'position' => $position,
        ],
    );
    ?>
</div>