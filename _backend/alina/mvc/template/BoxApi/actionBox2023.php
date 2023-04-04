<?php
/**@var object $data */
?>
<div>
    <h1>Box API</h1>
    <div class="ck-content">

        <pre><?= \alina\utils\Data::hlpGetBeautifulJsonString($data) ?></pre>
        <div class="m-5">&nbsp;</div>
        <div>
            <iframe src="<?= $data->strUrlPreview ?>" class="iframe-preview"></iframe>
        </div>
    </div>
</div>
<style>
    .iframe-preview {
        display: block;
        width: 50vw;
        margin: 0 auto;
        min-height: 50vh;
    }
</style>
