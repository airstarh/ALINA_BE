<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

$model  = $data->model->collection->first();
$table = $data->model->table;
$pkName = $data->model->pkName;
$sources = $data->sources;
?>
<h1 class="mt-3">Edit <?= $table ?> (<?= $model->{$pkName} ?>)</h1>
<?= htmlAlias::elForm([
    'action'  => '',
    'enctype' => 'multipart/form-data',
    'model'   => $model,
    'sources' => $sources,
]) ?>
