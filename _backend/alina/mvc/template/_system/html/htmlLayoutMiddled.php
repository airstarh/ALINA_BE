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
    <title><?= $data->pageTitle() ?></title>
    <?= $data->js() ?>
    <?= $data->css() ?>
</head>
<body>
<div id="alina-body-wrapper">
    <?= (new html())->piece('/_system/html/menu.php') ?>
    <div class="container h-100">
        <div class="row align-items-center h-100">
            <div class="col">

                <div class="row">
                    <div class="col-6 mx-auto">
                        <?= $data->content(); ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-8 mx-auto">
                        <?= $data->messages(); ?>
                    </div>
                </div>

            </div>
        </div>
    </div> <!-- /container -->
    <?php require_once(__DIR__ . '/_commonFooter.php') ?>
</div>
</body>
</html>
