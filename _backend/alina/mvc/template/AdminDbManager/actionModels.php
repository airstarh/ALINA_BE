<?php

use alina\Message;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Request as RequestAlias;
use alina\utils\Url;

/** @var $data stdClass */
/** @var $model _BaseAlinaModel */
$pagination   = $data->pagination;
$models       = $data->models;
$model        = $data->model;
$counter      = 0;
$formIdSearch = 'formIdSearch';
$GET          = \alina\utils\Request::obj()->GET;
if (count($models) <= 0) {
    Message::setWarning('There is no table data.');
    //AlinaRedirectIfNotAjax(Request::obj()->URL_PATH);

    return '';
}
$colHeaders = array_keys((array)$models[0]);
?>
<h1>Models <?= $model->table ?> <?= $pagination->rowsTotal ?></h1>
<?= (new htmlAlias)->piece('_system/html/_form/paginator.php', $pagination) ?>

<div class="table-responsive">
  <table class="table-sm table-striped table-hover  table-dark">
    <thead>
    <tr>
      <td></td>
        <?php foreach ($colHeaders as $h) {
            #####
            $nameSortField = $h;
            if ($model->tableHasField($h)) {
                $nameSortField = "{$model->table}.{$h}";
            }
            #####
            $clasAsc  = '';
            $clasDesc = '';
            if (isset($GET->sn) && isset($GET->sa)) {
                $clasAsc  = $GET->sn == $nameSortField && $GET->sa == 1 ? 'btn-info' : '';
                $clasDesc = $GET->sn == $nameSortField && $GET->sa == 0 ? 'btn-info' : '';
            }
            #####
            ?>
          <th class="text-nowrap">
            <a href="<?= Url::bizAddGetParamsToCurrentState('', ['sn' => $nameSortField, 'sa' => 1,]) ?>" class="btn <?= $clasAsc ?>">▲</a>
              <?= $h ?>
            <a href="<?= Url::bizAddGetParamsToCurrentState('', ['sn' => $nameSortField, 'sa' => 0,]) ?>" class="btn <?= $clasDesc ?>">▼</a>
          </th>
        <?php } ?>
    </tr>
    <tr>
      <td>
        <form
          id="<?= $formIdSearch ?>"
          action=""
          method="get"
        >
          <input type="hidden" name="form_id" value="<?= $formIdSearch ?>"/>
          <button type="submit" class="btn btn-sm btn-info m-1">Search</button>
          <br>
          <a class="btn btn-sm btn-warning m-1" href="<?= RequestAlias::obj()->URL_PATH ?>">Reset</a>
        </form>
      </td>
        <?php foreach ($colHeaders as $h) {
            $fNameLk     = "lk_{$h}";
            $fValueLk    = $GET->{$fNameLk} ?? '';
            $fNameEq     = "eq_{$h}";
            $fValueEq    = $GET->{$fNameEq} ?? '';
            $fNameNotLk  = "notlk_{$h}";
            $fValueNotLk = $GET->{$fNameNotLk} ?? '';
            $fNameGt     = "ggt_{$h}";
            $fValueGt    = $GET->{$fNameGt} ?? '';
            $fNameLt     = "llt_{$h}";
            $fValueLt    = $GET->{$fNameLt} ?? '';
            ?>
          <th>
            <input form="<?= $formIdSearch ?>" type="text" name="<?= $fNameLk ?>" value="<?= $fValueLk ?>" placeholder="LIKE">
            <br>
            <input form="<?= $formIdSearch ?>" type="text" name="<?= $fNameNotLk ?>" value="<?= $fValueNotLk ?>" placeholder="NOT LIKE">
            <br>
            <input form="<?= $formIdSearch ?>" type="text" name="<?= $fNameEq ?>" value="<?= $fValueEq ?>" placeholder="EQUALS">
            <br>
            <br>
            <input form="<?= $formIdSearch ?>" type="text" name="<?= $fNameGt ?>" value="<?= $fValueGt ?>" placeholder="&gt;">
            <br>
            <input form="<?= $formIdSearch ?>" type="text" name="<?= $fNameLt ?>" value="<?= $fValueLt ?>" placeholder="&lt;">
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