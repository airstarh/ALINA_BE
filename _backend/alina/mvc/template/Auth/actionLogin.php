<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<div class="row align-items-center h-100">
    <div class="col-md-6 mx-auto">
        <form action="" method="post" enctype="multipart/form-data">
            <h1>Login</h1>
            <input type="text" name="mail" value="<?= $data->mail ?>" placeholder="mail" class="form-control">
            <input type="password" name="password" value="<?= $data->password ?>" placeholder="Password" class="form-control">
            <?= htmlAlias::elFormStandardButtons()  ?>
        </form>
    </div>
</div>

