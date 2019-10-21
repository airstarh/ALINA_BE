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
    <?= (new html())->piece('/_system/html/menu.php') ?>
    <div class="container">

        <?= $data->messages(); ?>
        <?= $data->content(); ?>

        <!-- ToDo: Security! Delete on PROD.-->
        <?php if (ALINA_MODE !== 'PROD') : ?>
            <div>
                <?php
                $h1 = 'Alina Details';
                print_r("<h1>{$h1}</h1>");

                echo '<pre>';
                print_r(getallheaders());
                echo '</pre>';

                echo '<pre>';
                print_r($_SERVER);
                echo '</pre>';

                echo '<pre>';
                print_r(\alina\app::get()->router);
                echo '</pre>';

                //            echo '<pre>';
                //            print_r([
                //                'currentController' => \alina\app::get()->currentController,
                //                'currentAction'     => \alina\app::get()->currentAction,
                //            ]);
                //            echo '</pre>';

                print_r(\alina\utils\Sys::reportSpentTime());

                ?>
            </div>
        <?php endif; ?>

    </div> <!-- /container -->
</div>
</body>
</html>
