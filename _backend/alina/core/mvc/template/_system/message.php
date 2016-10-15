<?php /** @var \alina\core\message $data */ ?>
<div class="<?= $data->status ?>" id="message-id-<?= $data->id ?>">
    <pre><?= $data->messageRawText() ?></pre>
</div>

<?php
\alina\core\message::removeById($data->id);
?>