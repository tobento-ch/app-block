<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Article</title>
        <?= $view->render('inc/head') ?>
        <?= $view->assets()->render() ?>
    </head>
    <body>
        <main class="page-main">
            <?= $view->render('blocks.resource') ?>
        </main>
    </body>
</html>