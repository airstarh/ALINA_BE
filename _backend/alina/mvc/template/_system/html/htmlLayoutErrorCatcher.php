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
    <title><?= \alina\app::getConfig('title'); ?></title>
    <?= $data->js() ?>
    <?= $data->css() ?>
</head>
<body>
<div class="text-center"><h1>Oh... Ah... Error occured ¯\_(ツ)_/¯ </h1></div>
<div id="alina-body-wrapper">
    <div class="container">
        <h2><a href="/">Go Home</a></h2>
        <?= $data->messages(); ?>
        <?= $data->content(); ?>

        <!-- ToDo: Security! Delete on PROD.-->
        <?php if (\alina\mvc\model\CurrentUser::obj()->isAdmin()) : ?>
            <div>
                <?php
                $h1 = 'Alina Details';
                print_r("<h1>{$h1}</h1>");
                echo '<pre>';
                print_r(Sys::SUPER_DEBUG_INFO());
                echo '</pre>';
                print_r(\alina\utils\Sys::reportSpentTime());

                ?>
            </div>
        <?php endif; ?>

    </div> <!-- /container -->
</div>
</body>
</html>
