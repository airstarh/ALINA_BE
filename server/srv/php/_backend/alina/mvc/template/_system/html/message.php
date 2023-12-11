<?php
/** @var \alina\Message | \alina\MessageAdmin $data */
/** @var \alina\Message | \alina\MessageAdmin $class */

$class = get_class($data);
?>
<div class="<?= $class::$statusClasses[$data->status] ?>" id="message-id-<?= $data->id ?>">
    <?php
    $htmlString = $data->messageRawText();
    $htmlString = str_replace([PHP_EOL, "\n\r", "\r\n", "\n", "\r"], "</br>", $htmlString);
    ?>
    <?= $htmlString ?>
</div>
