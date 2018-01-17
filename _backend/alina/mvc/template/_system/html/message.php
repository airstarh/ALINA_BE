<?php /** @var \alina\message $data */ ?>
<div class="<?= $data->status ?>" id="message-id-<?= $data->id ?>">
	<?= $data->messageRawText() ?>
</div>

<?php
\alina\message::removeById($data->id);
?>