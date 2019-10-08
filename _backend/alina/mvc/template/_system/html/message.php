<?php /** @var \alina\message $data */ ?>
	<div class="<?= $data->status ?>" id="message-id-<?= $data->id ?>">
        <?php
        $htmlString = $data->messageRawText();
        $htmlString = str_replace([PHP_EOL,"\n\r", "\r\n","\n", "\r"], "</br>", $htmlString);
        ?>
        <?= $htmlString ?>
	</div>

<?php
\alina\message::removeById($data->id);
?>
