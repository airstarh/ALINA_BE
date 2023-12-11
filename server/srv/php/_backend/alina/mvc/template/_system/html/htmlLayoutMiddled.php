<?php
/** @var $data html */

use alina\mvc\View\html;
use alina\Utils\Sys;

?>
<!DOCTYPE html style="background-color: #343a40; color: #fff;">
<html lang="en">
<head>
    <? require_once '_commonHead.php' ?>
</head>
<body id="alina-real-body" class="alina-main-bg alina-main-txt">
<div class="alina-flex-vertical-container alina-vh-100">
    <div class="alina-flex-vertical-header">
        <?= (new html())->piece(html::$htmlMenu) ?>
    </div>
    <div class="alina-flex-vertical-content alina-flex-item-wide">
        &nbsp;
    </div>
    <div class="alina-flex-vertical-content alina-flex-item-shrink container">
        <?= $data->content(); ?>
        <?= $data->messages(); ?>
    </div>
    <div class="alina-flex-vertical-content alina-flex-item-wide">
        &nbsp;
    </div>
    <div class="alina-flex-vertical-footer">
        <?= (new html())->piece(html::$htmlFooter) ?>
    </div>
</div>
<? require_once '_commonFooter2.php' ?>
</body>
</html>
