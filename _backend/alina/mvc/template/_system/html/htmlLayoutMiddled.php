<?php
/** @var $data html */

use alina\mvc\view\html;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= \alina\app::getConfig('title'); ?></title>
    <?= $data->css() ?>
</head>
<body>
<div id="alina-body-wrapper">
    <?= (new html())->piece('/_system/html/menu.php') ?>
    <div class="container h-100">
        <?= $data->messages(); ?>
        <?= $data->content(); ?>
    </div> <!-- /container -->
</div>
<!--region JS -->
<?= $data->js() ?>
<!--endregion JS -->
</body>
</html>
