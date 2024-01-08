<?php
/**@var $data array */

use alina\mvc\Controller\AdminDbManager;
use alina\mvc\Controller\Pm;
use alina\mvc\View\html;

$vd  = $data;
$GET = \alina\Utils\Request::obj()->GET;
?>
<div class="container-fluid">
    <div class="row">
        <div class="col">
            <!--##################################################-->
            <!--region PAGE-->

            <div class="clear">&nbsp;</div>
            <h1>
                <?= ___("Report") ?>
            </h1>

            <div class="clear">&nbsp;</div>

            <!--##################################################-->
            <!--region DATE PICKER-->
            <form action="" method="get">
                <div class="row">
                    <div class="col text-center">
                        <label>
                            <input type="date" name="date_start" value="<?= $GET->date_start ?? null ?>"/>
                        </label>
                        &nbsp;&nbsp;&nbsp;
                        <label>
                            <input type="date" name="date_end" value="<?= $GET->date_end ?? null ?>"/>
                        </label>
                    </div>
                </div>
                <div>
                    <?= html::elFormStandardButtons([]) ?>
                </div>
            </form>
            <form action="" method="post">
                <input type="hidden" name="form_id" value="freeze_period">
                <input type="hidden" name="do" value="freeze_period">
                <input type="hidden" name="date_start" value="<?= $GET->date_start ?? null ?>">
                <input type="hidden" name="date_end" value="<?= $GET->date_end ?? null ?>">
                <button type="submit"
                        class="brn brn-success"
                ><?= ___('Freeze this period') ?></button>
            </form>
            <!--endregion DATE PICKER-->
            <!--##################################################-->


            <div class="clear">&nbsp;</div>
            <? ###= (new html)->piece('_system/html/_form/table002.php', $data['res']) ?>
            <?= (new html)->piece('_system/html/_form/table002.php', $vd['zzz']) ?>
            <div class="clear">&nbsp;</div>


            <!--##################################################-->
            <h2><?= ___('Totals by Users') ?></h2>
            <?= (new html)->piece('_system/html/_form/table002.php', $vd['byUsers']) ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->


            <!--##################################################-->
            <h2><?= ___('Totals by Organization') ?></h2>
            <?= (new html)->piece('_system/html/_form/table002.php', $vd['od']) ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->


            <!--##################################################-->
            <h2><?= ___('Totals by Department') ?></h2>
            <?= (new html)->piece('_system/html/_form/table002.php', $vd['dd']) ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->


            <!--##################################################-->
            <h2><?= ___('Totals by Project') ?></h2>
            <?= (new html)->piece('_system/html/_form/table002.php', $vd['pd']) ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->


            <!--##################################################-->
            <h2><?= ___('Each User Detalization') ?></h2>
            <?php foreach ($vd['ud'] as $uid => $user): ?>
                <?= (new html)->piece('_system/html/_form/table002.php', $user) ?>
                <div class="clear">&nbsp;</div>
            <?php endforeach; ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->


            <!--##################################################-->
            <div class="ck-content">
<pre>
<?php
print_r($vd['zzz']);
?>
</pre>
            </div>
            <!--##################################################-->

            <!--##################################################-->

            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

