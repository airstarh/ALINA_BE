<?php /** @var $data stdClass */ ?>
<?php

use alina\mvc\view\html as htmlAlias;

?>
<div>
  <form action="" method="post" enctype="multipart/form-data">
    <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
      <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

      <?= (new htmlAlias)->piece('_system/html/_form/dbConnectCredentials.php', $data) ?>

      <?= (new htmlAlias)->piece('_system/html/_form/selectOneSimple.php', \alina\utils\Data::mergeObjects($data, (object)[
          'name'        => 'tableName',
          'value'       => $data->tableName,
          'options'     => $data->arrTables,
          'placeholder' => '--- select a table ---',
      ])) ?>

      <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>
  </form>
  <div class="mt-3">
    <ul class="list-group-item-dark">
      <li class="list-group-item d-flex justify-content-between align-items-center">Total
                                                                                    Fields: <?= $data->arrColumnsCount ?></li>
      <li class="list-group-item d-flex justify-content-between align-items-center">Primary
                                                                                    Key: <?= $data->pkName ?></li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        Rows Total: <?= $data->rowsInTable ?></li>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <a href="/admindbmanager/models/<?= $data->tableName ?>">
          Manage Rows <?= $data->tableName ?>
        </a>
    </ul>
  </div>
  <div class="container-sm">
    <div class="row">
      <div class="col-sm">
        <div class="mt-3">
            <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                'title' => 'SELECT',
                'badge' => 'strSqlSELECT',
            ]) ?>
          <textarea
            class="form-control" rows="11"
          ><?= $data->strSqlSELECT ?></textarea>
        </div>
      </div>
      <div class="col-sm">
        <div class="mt-3">
            <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                'title' => 'INSERT',
                'badge' => 'strSqlINSERT',
            ]) ?>
          <textarea
            class="form-control" rows="11"
          ><?= $data->strSqlINSERT ?></textarea>
        </div>

      </div>
      <div class="col-sm">

        <div class="mt-3">
            <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                'title' => 'UPDATE',
                'badge' => 'strSqlUPDATE',
            ]) ?>
          <textarea
            class="form-control" rows="11"
          ><?= $data->strSqlUPDATE ?></textarea>
        </div>

      </div>
      <div class="col-sm">
        <div class="mt-3">
            <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                'title' => 'DELETE',
                'badge' => 'strSqlDELETE',
            ]) ?>
          <textarea
            class="form-control" rows="11"
          ><?= $data->strSqlDELETE ?></textarea>
        </div>
      </div>
    </div>
  </div>
  <div>
    <!-- ##################################################-->
    <!-- ##################################################-->
    <!-- ##################################################-->
    <div class="mt-3">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'PDO bind',
            'badge' => 'strSqlPDObind',
        ]) ?>
      <textarea
        class="form-control" rows="11"
      ><?= $data->strSqlPDObind ?></textarea>
    </div>
    <!-- ##################################################-->
    <!-- ##################################################-->
    <!-- ##################################################-->
    <div class="container-sm mt-3">
      <div class="row">
        <div class="col-sm">
            <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                'title' => 'JSON',
                'badge' => 'colsAsJson',
            ]) ?>
          <textarea
            class="form-control" rows="11"
          ><?= $data->colsAsJson ?></textarea>
        </div>
        <div class="col-sm">
            <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
                'title' => 'PHP Array',
                'badge' => 'colsAsPHPArr',
            ]) ?>
          <textarea
            class="form-control" rows="11"
          ><?= $data->colsAsPHPArr ?></textarea>
        </div>
        <!--                <div class="col-sm"></div>-->
      </div>
    </div>
    <!-- ##################################################-->
    <!-- ##################################################-->
    <div class="mt-5">
        <?= (new htmlAlias)->piece('_system/html/tag/bootstrapBadge.php', (object)[
            'title' => 'Fields` details',
            'badge' => 'tColsInfo',
        ]) ?>
        <?= (new htmlAlias)->piece('_system/html/_form/table001.php', \alina\utils\Data::mergeObjects($data, (object)[
            'arr' => $data->tColsInfo,
        ])) ?>
    </div>
    <!-- ##################################################-->
    <!-- ##################################################-->
    <!-- ##################################################-->
  </div>


    <?php
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    ?>
</div>
