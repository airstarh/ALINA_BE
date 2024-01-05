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
                    <div class="col">
                        <label>
                            <input type="date" name="date_start" value="<?= $GET->date_start ?? null ?>"/>
                        </label>
                    </div>
                    <div class="col">
                        <label>
                            <input type="date" name="date_end" value="<?= $GET->date_end ?? null ?>"/>
                        </label>
                    </div>
                </div>
                <div>
                    <?= html::elFormStandardButtons([]) ?>
                </div>
            </form>
            <!--endregion DATE PICKER-->
            <!--##################################################-->
            <div class="clear">&nbsp;</div>
            <?= (new html)->piece('_system/html/_form/table002.php', $data['res']) ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->
            <h2><?= ___('Totals') ?></h2>
            <!--##################################################-->
            <?= (new html)->piece('_system/html/_form/table002.php', $vd['byUsers']) ?>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->
            <div class="clear">&nbsp;</div>
            <!--##################################################-->
            <h2><?= ___('Each User Detalization') ?></h2>
            <!--##################################################-->
            <?php foreach ($vd['ud'] as $uid => $user): ?>
                <?= (new html)->piece('_system/html/_form/table002.php', $user) ?>
                <div class="clear">&nbsp;</div>
            <?php endforeach; ?>

            <div class="clear">&nbsp;</div>
            <!--##################################################-->
            <div class="clear">&nbsp;</div>
            <div class="ck-content">
<pre>
<?php
print_r($vd['ud']);
?>
</pre>
            </div>
            <div class="clear">&nbsp;</div>
            <!--##################################################-->

            <!--##################################################-->

            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

