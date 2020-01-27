<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<form action="" method="post" enctype="multipart/form-data">
    <input type="password" name="password" value="<?= $data->password ?>" placeholder="Password" class="form-control">
    <input type="password" name="confirm_password" value="<?= $data->confirm_password ?>" placeholder="Password again" class="form-control">
    <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
    <input type="hidden" name="route_plan_b" value="<?= $data->route_plan_b ?>">
    <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>
</form>
