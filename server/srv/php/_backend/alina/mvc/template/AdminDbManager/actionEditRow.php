<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;

$model   = $data->model->attributes;
$table   = $data->model->table;
$pkName  = $data->model->pkName;
$sources = $data->sources;

?>
<h1 class="mt-3">Edit <?= $table ?> (<?= $model->{$pkName} ?>)</h1>
<div class="notranslate ck ck-content p-5">
    <?= htmlAlias::elForm((object)[
        'action'  => '',
        'enctype' => 'multipart/form-data',
        'model'   => $model,
        'sources' => $sources,
    ]) ?>
</div>