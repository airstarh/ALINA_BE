<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<form action="" method="post" enctype="multipart/form-data">
    <h1>Request password reset</h1>
    <input type="hidden" name="form_id" value="<?= $data->form_id ?>" placeholder="Password" class="form-control">
    <input type="text" name="mail" value="<?= $data->mail ?>" placeholder="mail" class="form-control">
    <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>
</form>
