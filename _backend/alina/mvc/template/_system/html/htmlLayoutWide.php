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
    <?= $data->js() ?>
    <?= $data->css() ?>
</head>
<body>
<div id="alina-body-wrapper">
    <?= (new \alina\mvc\view\html())->piece('/_system/html/menu.php') ?>
    <div class="container-sm">

        <?= $data->messages(); ?>
        <?= $data->content(); ?>
    </div> <!-- /container -->
    <?php require_once(__DIR__ . '/_commonFooter.php') ?>
</div>
</body>
</html>
