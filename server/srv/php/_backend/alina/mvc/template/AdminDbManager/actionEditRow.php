<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;

$model   = $data->model->attributes;
$table   = $data->model->table;
$pkName  = $data->model->pkName;
$sources = $data->sources;

?>
<h1 class="mt-3">Edit <?= $table ?> (<?= $model->{$pkName} ?>)</h1>
<a
        href="/admindbmanager/models/<?= $data->model->table ?>"
        class="btn btn-primary"
>List Models</a>

<a
        href="/admindbmanager/editrow/<?= $data->model->table ?>/new"
        class="btn btn-primary"
>Create New</a>

<div class="notranslate">
    <?= htmlAlias::elForm((object)[
        'action'  => '',
        'enctype' => 'multipart/form-data',
        'model'   => $model,
        'sources' => $sources,
    ]) ?>
</div>