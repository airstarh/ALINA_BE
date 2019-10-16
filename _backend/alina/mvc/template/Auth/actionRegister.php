<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<div class="row align-items-center h-100">
    <div class="col-md-6 mx-auto">
        <form action="" method="post" enctype="multipart/form-data">
            <h1>Register</h1>
            <input type="text" name="mail" value="<?= $data->mail ?>" placeholder="mail" class="form-control">
            <input type="password" name="password" placeholder="Password" class="form-control">
            <input type="password" name="confirm_password" placeholder="Password again" class="form-control">
            <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>
        </form>
        <?php
        echo '<pre>';
        print_r($data);
        echo '</pre>';
        ?>
    </div>
</div>

