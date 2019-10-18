<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

$item = $data->user->collection->first();

?>
<h1>Profile for <?= $item->mail ?></h1>

<?= htmlAlias::elForm([
    'action'  => '',
    'enctype' => 'multipart/form-data',
    'source'  => $item,
]) ?>
