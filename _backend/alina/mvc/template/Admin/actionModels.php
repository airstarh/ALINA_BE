<?php

use alina\Message;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;

/** @var $data stdClass */
$pagination = $data->pagination;
$models     = $data->models;
$model      = $data->model;
$counter    = 0;
if (count($models) <= 0) {
    Message::setWarning('There is no table data.');

    return '';
}
$colHeaders = array_keys((array)$models[0]);
?>
<h1>Models</h1>
<?= (new htmlAlias)->piece('_system/html/_form/paginator.php', $pagination) ?>

<div class="table-responsive">
  <table class="table table-striped table-hover  table-dark">
    <thead>
    <tr>
      <td></td>
        <?php foreach ($colHeaders as $h) { ?>
          <th>
              <?= $h ?>
          </th>
        <?php } ?>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($models as $m) { ?>
      <tr>
        <td><?= ++$counter ?></td>
          <?php foreach ($m as $f => $v) {
              $_f = substr(strip_tags($f), 0, 200);
              $_v = substr(strip_tags(Data::stringify($v)), 0, 200);
              ?>
            <td>
                <?php if (Data::isIterable($v)) { ?>
                  <ul class="list-group">
                      <?php foreach ($v as $i => $d) { ?>
                        <li class="list-group-item-dark d-flex justify-content-between align-items-center text-nowrap">
                          (<?= $i ?>) <?= Data::stringify($d) ?>
                        </li>
                      <?php } ?>
                  </ul>
                <?php } else { ?>
                  <div><?= $_v ?></div>
                    <?php if ($f === $model->pkName) { ?>
                    <div>
                      <a
                        href="/admindbmanager/editrow/<?= $model->table ?>/<?= $v ?>"
                        class="btn btn-sm btn-info"
                      >Edit</a>
                    </div>
                    <?php } ?>
                <?php } ?>
            </td>
          <?php } ?>
      </tr>
    <?php } ?>
    </tbody>
  </table>
</div>