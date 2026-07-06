<?php
/** @var $data html */

use alina\mvc\View\html;

?>
<!DOCTYPE html>
<html lang="en" style="background-color: #343a40; color: #fff;">
<head>
    <? require_once '_commonHead.php' ?>
</head>
<body id="alina-real-body" class="alina-main-bg alina-main-txt">
<?= $data->messages(); ?>
<?= $data->content(); ?>
</body>
</html>
