<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<div class="row align-items-center h-100">
    <div class="col-md-6 mx-auto">
        <form action="" method="post" enctype="multipart/form-data">
            <h1>Reset password with code</h1>
            <input type="text" name="mail" value="<?= $data->mail ?>" placeholder="mail" class="form-control">
            <input type="text" name="reset_code" value="<?= $data->reset_code ?>" placeholder="reset code" class="form-control">
            <input type="password" name="password" value="<?= $data->password ?>" placeholder="Password" class="form-control">
            <input type="password" name="confirm_password" value="<?= $data->confirm_password ?>" placeholder="Password again" class="form-control">
            <input type="hidden" name="form_id" value="<?= $data->form_id ?>" placeholder="Password" class="form-control">
            <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>
        </form>
    </div>
</div>

