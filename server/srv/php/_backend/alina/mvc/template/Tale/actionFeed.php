<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;

?>
<!--<textarea name="" id="" rows="5" class="form-control">--><? //= var_export($data, 1) ?><!--</textarea>-->
<?php foreach ($data->tale as $tale) { ?>
    <!-- ##################################################-->
    <div>
        <?= (new htmlAlias)->piece('/Tale/actionUpsert.php', $tale) ?>
    </div>
    <div class="clearfix mb-5">&nbsp</div>
<?php } ?>
