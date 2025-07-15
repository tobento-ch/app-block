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
        <header class="page-header">
            <?= $view->render('blocks.header') ?>
        </header>
        <main class="page-main">
            <?= $view->render('blocks.resource') ?>
            <p>Some content</p>
            <?= $view->render('blocks.resource.footer') ?>
        </main>
        <footer class="page-footer">
            <?= $view->render('blocks.footer') ?>
        </footer>
    </body>
</html>