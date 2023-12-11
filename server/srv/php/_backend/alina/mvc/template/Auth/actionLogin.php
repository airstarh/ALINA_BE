<?php
/** @var $data stdClass */

use alina\mvc\View\html as htmlAlias;

?>
<form action="" method="post" enctype="multipart/form-data">
    <h1>Login</h1>
    <input type="text" name="mail" value="<?= $data->mail ?>" placeholder="Mail" class="form-control">
    <input type="password" name="password" value="<?= $data->password ?>" placeholder="Password" class="form-control">
    <input type="hidden" name="form_id" value="<?= $data->form_id ?>" placeholder="Password" class="form-control">
    <?= htmlAlias::elFormStandardButtons() ?>
    <div>
        <a href="/auth/ResetPasswordRequest">Reset password</a>
    </div>
</form>
