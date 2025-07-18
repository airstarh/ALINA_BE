<?php /** @var $data array|object */

use alina\mvc\View\html as htmlAlias; ?>
<div class="row align-items-center h-100">
    <div class="col mx-auto">
        <form enctype="multipart/form-data" action="" method="POST">
            <input type="hidden" name="MAX_FILE_SIZE" value="9999999999"/>
            <input type="hidden" name="form_id" value="actionCommon"/>
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text">Upload</span>
                </div>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="inputGroupFile01" multiple name="<?= ALINA_FILE_UPLOAD_KEY ?>[]">
                    <label class="custom-file-label" for="inputGroupFile01">Choose file</label>
                </div>
            </div>


            <?= htmlAlias::elFormStandardButtons() ?>
        </form>
    </div>
</div>


