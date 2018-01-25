<?php /** @var \alina\message $data */ ?>
	<div class="<?= $data->status ?>" id="message-id-<?= $data->id ?>">
        <?php
        $htmlString = $data->messageRawText();
        $NL         = PHP_EOL;
        $htmlString = str_replace(["\r\n", "\n", "\r"], "</br>", $htmlString);
        ?>
        <?= $htmlString ?>
	</div>

<?php
\alina\message::removeById($data->id);
?>