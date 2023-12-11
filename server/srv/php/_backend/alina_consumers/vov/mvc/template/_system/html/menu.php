<?php

use alina\mvc\Model\CurrentUser;
use alina\Utils\Html;

$cu = alina\mvc\Model\CurrentUser::obj();
$ua = $cu->attributes();
?>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="/"><?= AlinaCfg('title'); ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
    </nav>
