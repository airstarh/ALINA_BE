<?php
/** @var $data html */

use alina\mvc\view\html;

?>
<!DOCTYPE html>
<html lang="en" style="background-color: #343a40; color: #fff;">
<head>
    <? require_once '_commonHead.php' ?>
</head>
<body id="alina-real-body" class="alina-main-bg alina-main-txt">

<div class="alina-flex-vertical-container alina-vh-100">
    <div class="alina-flex-vertical-header">
        <?= (new html())->piece(html::$htmlMenu) ?>
    </div>
    <div class="alina-flex-vertical-content p-3">
        <div class="container-fluid">
            <?= $data->messages(); ?>
            <?= $data->content(); ?>
        </div>
    </div>
    <div class="alina-flex-vertical-footer" v-if="!fullScreen">
        <?= (new html())->piece(html::$htmlFooter) ?>
    </div>
</div>
<? require_once '_commonFooter2.php' ?>
</body>
</html>
