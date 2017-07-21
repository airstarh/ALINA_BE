<?php /** @var $data \alina\mvc\view\html */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=9"/>
    <title><?= \alina\app::getConfig('title'); ?></title>
    <?= $data->css(); ?>
    <?= $data->js(); ?>
</head>
<body>
<div class="body_wrapper">
    <div class="page-content">
        <h1><?= \alina\app::getConfig('title'); ?></h1>
        <div><a href="<?= ref('/'); ?>">Go Home</a></div>
        <?= $data->messages(); ?>
        <?= $data->content(); ?>
    </div>
</div>
<div>
    <?php
    // ToDo: Delete on PROD.
    if (ALINA_MODE !== 'PROD') {
        $__FILE__ = __FILE__;
        print_r("<h1>{$__FILE__}</h1>");
        echo '<pre>';
        print_r(\alina\app::get()->router);
        echo '</pre>';

        echo '<pre>';
        print_r([
                'currentController' => \alina\app::get()->currentController,
                'currentAction' => \alina\app::get()->currentAction,
        ]);
        echo '</pre>';
    }

    $alinaTimeSpent = microtime(TRUE) - ALINA_MICROTIME;
    print_r("<h2>Time spent: $alinaTimeSpent</h2>");

    ?>
</div>
</body>
</html>