<?php /** @var $data stdClass */ ?>
<?php

use alina\mvc\view\html as htmlAlias;

?>
<div>
    <form action="." method="post" enctype="multipart/form-data">
        <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

        <?= (new htmlAlias)->piece('_system/html/_form/dbConnectCredentials.php', $data) ?>

        <?= (new htmlAlias)->piece('_system/html/_form/selectOneSimple.php', hlpMergeSimpleObjects($data, (object)[
            'name' => 'tableName',
            'value' => $data->tableName,
            'options' => $data->arrTables,
        ])) ?>



        <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>
    </form>
    <div class="container-sm">
        <div class="row">
            <div class="col-sm">
                <div class="mt-3">
                        <span class="btn btn-primary">
                            SELECT <span class="badge badge-light">strSqlSELECT</span>
                        </span>
                    <textarea class="form-control" rows="11"
                    ><?= $data->strSqlSELECT ?></textarea>
                </div>
            </div>
            <div class="col-sm">

                <div class="mt-3">
                        <span class="btn btn-primary">
                            INSERT <span class="badge badge-light">strSqlINSERT</span>
                        </span>
                    <textarea class="form-control" rows="11"
                    ><?= $data->strSqlINSERT ?></textarea>
                </div>

            </div>
            <div class="col-sm">

                <div class="mt-3">
                        <span class="btn btn-primary">
                            UPDATE <span class="badge badge-light">strSqlUPDATE</span>
                        </span>
                    <textarea class="form-control" rows="11"
                    ><?= $data->strSqlUPDATE ?></textarea>
                </div>

            </div>
            <div class="col-sm">
                <div class="mt-3">
                        <span class="btn btn-primary">
                            DELETE <span class="badge badge-light">strSqlDELETE</span>
                        </span>
                    <textarea class="form-control" rows="11"
                    ><?= $data->strSqlDELETE ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <div>
        <div class="mt-3">
            <span class="btn btn-primary">
                PDO bind <span class="badge badge-light">strSqlPDObind</span>
            </span>
            <textarea class="form-control" rows="11"
            ><?= $data->strSqlPDObind ?></textarea>
        </div>
    </div>


    <?php
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    ?>
</div>
