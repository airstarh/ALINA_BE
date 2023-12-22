<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;

$model   = $data->model->attributes;
$table   = $data->model->table;
$pkName  = $data->model->pkName;
$sources = $data->sources;

?>
<div class="clear m-1">&nbsp;</div>
<h1><?= $table ?> <sup>[ID: <?= $model->{$pkName} ?>]</sup></h1>
<div class="clear m-1">&nbsp;</div>
<a
        href="/admindbmanager/models/<?= $data->model->table ?>"
        class="btn btn-primary"
>List Models</a>

<a
        href="/admindbmanager/editrow/<?= $data->model->table ?>/new"
        class="btn btn-primary"
>Create New</a>

<?php if ($model->id): ?>
    <a
            href="/admindbmanager/delete/<?= $data->model->table ?>/<?= $model->id ?>"
            class="btn btn-danger"
    >Delete</a>
<?php endif ?>

<div class="notranslate">
    <?= htmlAlias::elForm((object)[
        'action'  => '',
        'enctype' => 'multipart/form-data',
        'model'   => $model,
        'sources' => $sources,
    ]) ?>
</div>