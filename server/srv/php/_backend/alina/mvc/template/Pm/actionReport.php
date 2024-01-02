<?php
/**@var $data array */

use alina\mvc\Controller\AdminDbManager;
use alina\mvc\Controller\Pm;
use alina\mvc\View\html;

$GET = \alina\Utils\Request::obj()->GET;
?>
<div class="container">
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

            <!--##################################################-->
            <div class="ck-content">
<pre>
    <?php
    print_r($data);
    ?>
</pre>
            </div>
            <!--##################################################-->

            <!--endregion PAGE-->
            <!--##################################################-->
        </div>
    </div>
</div>

