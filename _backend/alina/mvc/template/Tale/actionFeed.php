<?php
/** @var $data stdClass */
?>
<textarea name="" id="" rows="5" class="form-control"><?= var_export($data, 1) ?></textarea>
<?php foreach ($data->tales as $tale) { ?>
    <div>
        <h1><?= $tale->header ?> (<span><?= $tale->id ?></span>/<span><?= $tale->owner_id ?></span>)</h1>
        <div class="ck ck-content bg-light"><?= $tale->body ?></div>
    </div>
    <div class="clearfix"></div>
    <form action="">
        <input type="text" name="comment" value="" placeholder="Comment" class="form-control">
    </form>
    <div class="clearfix"></div>
<?php } ?>
