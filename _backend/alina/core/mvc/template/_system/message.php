<?php /** @var \alina\message $data */ ?>
<div class="<?= $data->status ?>" id="message-id-<?= $data->id ?>">
    <pre><?= $data->messageRawText() ?></pre>
</div>

<?php
\alina\message::removeById($data->id);
?>