<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

$modell  = $data->user->collection->first();
$sources = $data->sources;
?>
<h1 class="mt-3">Profile for <?= $modell->mail ?></h1>
<?= htmlAlias::elForm([
    'action'  => '',
    'enctype' => 'multipart/form-data',
    'model'   => $modell,
    'sources' => $sources,
]) ?>
