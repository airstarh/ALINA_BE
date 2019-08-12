<div id="array-serializer">
    <form action="/ArraySerialize/index" method="post" enctype="multipart/form-data">
        <label>STRING [Serialized array]:<br></label>
        <textarea name="strSource" class="form-control w-100" rows="10"><?= $data->strSource ?></textarea>
        <br>
        <input type="text" name="strFrom" value="<?= $data->strFrom ?>">
        <br>
        <input type="text" name="strTo" value="<?= $data->strTo ?>">
        <h1>
            <button type="submit" class="btn btn-lg btn-primary">Go!</button>
            <a href="." class="btn btn-danger">RESET</a>
        </h1>

    </form>

    <div>
        <label>RESULT:<br></label>
        <textarea name="" class="form-control w-100" rows="10"><?= $data->strRes ?></textarea>
    </div>
    <div>
        <h1>CONTROL</h1>

        <div class="row">
            <div class="col-6">
                <h3>arrRes</h3>
            </div>
            <div class="col-6">
                <h3>arrResControl</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-6">
                <?= mb_strlen($data->strRes) ?>
            </div>
            <div class="col-6">
                <?= mb_strlen($data->strResControl) ?>
            </div>
        </div>

        <div class="row">
            <div class="col-6">
                <?php
                echo '<pre>';
                print_r($data->arrRes);
                echo '</pre>';
                ?>
            </div>
            <div class="col-6">
                <?php
                echo '<pre>';
                print_r($data->arrResControl);
                echo '</pre>';
                ?>
            </div>
        </div>
    </div>
</div>
