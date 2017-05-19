<?php  /** @var $data \alina\mvc\view\html */ ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=9" />
    <title><?= \alina\app::getConfig('title'); ?></title>
    <?= $data->css(); ?>
    <?= $data->js(); ?>
</head>
<body>
<div class="body_wrapper">
    <div class="page-content">
        <h1><?= \alina\app::getConfig('title'); ?></h1>
        <br/>
        <h2>
            <a href="<?= ref('/'); ?>">Go Home Page</a>
        </h2>
        <br/>
        <?= $data->content; ?>
        <br/>
    </div>
</div>
</body>
</html>