<div>
    <form action="/ArraySerialize/index" method="post" enctype="multipart/form-data">
        <label>String. Serialized array:<br>
            <textarea name="strSource" cols="100" rows="5"><?= $data->strSource ?></textarea>
        </label>
        <br>
        <h1><button type="submit">Go!</button></h1>
    </form>

    <div>
        <label>RESULT:<br>
            <textarea name="" id="" cols="100" rows="10"><?= $data->strRes ?></textarea>
        </label>
    </div>
    <div>
        <pre>
            <?php
            echo '<pre>';
            print_r($data->arrRes);
            echo '</pre>';
            ?>
        </pre>
    </div>
</div>
