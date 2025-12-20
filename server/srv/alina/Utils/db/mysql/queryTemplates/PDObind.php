<?php /** @var $data stdClass */ ?>
<?php foreach ($data->arrColumns as $col) { ?>
    $stmt->bindParam(':<?= $col ?>', $data-><?= $col ?>);
<?php } ?>
