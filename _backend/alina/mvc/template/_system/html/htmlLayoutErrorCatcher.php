<?php
/** @var $data html */

use alina\mvc\view\html;
use alina\utils\Sys;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= AlinaCfg('title'); ?></title>
    <?= $data->js() ?>
    <?= $data->css() ?>
</head>
<body>
<div class="text-center">
    <h1>Oh... Ah... Error happened <a title="Return to home page" href="/">¯\_(ツ)_/¯</a></h1>
</div>
<div id="alina-body-wrapper" class="bg-dark text-white">
    <div class="container">
        <?= $data->messages(); ?>
        <?= $data->content(); ?>
    </div> <!-- /container -->
    <?php require_once(__DIR__ . '/_commonFooter.php') ?>
</div>
</body>
</html>
