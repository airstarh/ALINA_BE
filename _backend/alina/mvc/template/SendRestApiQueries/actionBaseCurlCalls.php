<?php

use alina\mvc\view\html as htmlAlias;

?>
<h1>HTTP Requests with Server-Side cURL utility</h1>
<div class="row">
    <div class="col-sm">
        <form action="" method="post" enctype="multipart/form-data">
            <h2>Request</h2>
            <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
            <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

            <div class="form-group mt-3">
                <span class="btn btn-primary">
                    URI <span class="badge badge-light">reqUri</span>
                </span>
                <input type="text" name="reqUri" value="<?= $data->reqUri ?>" class="form-control">
            </div>
            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label for="reqMethod">reqMethod</label>
                        <select class="form-control" id="reqMethod" name="reqMethod">
                            <option value="">-</option>
                            <?php foreach ($data->methods as $item => $mutator) { ?>
                                <option
                                    value="<?= $item ?>"
                                    <?= ($item === $data->reqMethod) ? 'selected' : '' ?>
                                ><?= $item ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm">
                    <div class="form-group mt-3">
                        <span class="btn btn-primary">
                            GET <span class="badge badge-light">reqGet</span>
                        </span>
                        <textarea
                            name="reqGet" class="form-control"
                            rows="11"
                        ><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->reqGet) ?></textarea>
                    </div>
                </div>
                <div class="col-sm">
                    <div class="form-group mt-3">
                        <span class="btn btn-primary">
                            POST <span class="badge badge-light">reqPost</span>
                        </span>
                        <textarea
                            name="reqPost" class="form-control" id="reqPost"
                            rows="11"
                        ><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->reqPost) ?></textarea>

                        <div class="custom-control custom-checkbox">
                            <input
                                name="reqPostRaw" <?= $data->reqPostRaw ? 'checked' : '' ?>
                                class="custom-control-input"
                                type="checkbox" value="1" id="reqPostRaw"
                            >
                            <label class="btn btn-primary custom-control-label" for="reqPostRaw">
                                reqPostRaw
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-group mt-3">
                <span class="btn btn-primary">
                    Headers <span class="badge badge-light">reqHeaders</span>
                </span>
                <textarea
                    name="reqHeaders" class="form-control"
                    rows="5"
                ><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->reqHeaders) ?></textarea>
            </div>

            <div class="form-group mt-3">
                <span class="btn btn-primary">
                    Cookie <span class="badge badge-light">reqCookie</span>
                </span>
                <textarea
                    name="reqCookie" class="form-control"
                    rows="5"
                ><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->reqCookie) ?></textarea>
            </div>

            <?= (new htmlAlias)->piece('_system/html/_form/standardFormButtons.php') ?>

        </form>
    </div>
    <!-- ######################################## -->
    <!-- ######################################## -->
    <!-- ######################################## -->
    <div class="col-sm">
        <div class="mt-3">
            <h2>Response</h2>
            <div class="mt-3">
                <span class="btn btn-primary">
                    URI <span class="badge badge-light">$data->q->resUrl</span>
                </span>
                <input type="text" value="<?= $data->q->resUrl ?>" class="form-control">
            </div>

            <div class="mt-3">
                <span class="btn btn-primary">
                    Body <span class="badge badge-light">$data->q->respBody</span>
                </span>
                <textarea
                    class="form-control w-100" rows="11"
                ><?= htmlentities(\alina\utils\Data::hlpGetBeautifulJsonString($data->q->respBody)) ?></textarea>
            </div>

            <div class="mt-3">
                <span class="btn btn-primary">
                    Headers <span class="badge badge-light">$data->q->respHeadersStructurized </span>
                </span>
                <textarea
                    class="form-control w-100"
                    rows="11"
                ><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->q->respHeadersStructurized) ?></textarea>
            </div>

            <div class="mt-3">
                <span class="btn btn-primary">
                    curlInfo <span class="badge badge-light">$data->q->curlInfo </span>
                </span>
                <textarea
                    class="form-control w-100"
                    rows="11"
                ><?= \alina\utils\Data::hlpGetBeautifulJsonString($data->q->curlInfo) ?></textarea>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm">
        <div class="m-5">
            <span class="btn btn-primary">
                iframe <span class="badge badge-light">$data->q->respBody</span>
            </span>
            <div class="m5 clear">&nbsp;</div>
            <!--<?= $data->q->resUrl ?>-->
            <iframe id="alina-dynamic-request-result" src="" class="w-100" height="500"></iframe>
            <template id="respBody">
                <?= $data->q->respBody ?>
            </template>
            <script type="text/javascript">
                var tpl    = document.querySelector('#respBody');
                //var iframe = document.querySelector('#alina-dynamic-request-result');
                var iframe = document.querySelector('#alina-dynamic-request-result').contentWindow.document.body;
                var clon   = tpl.content.cloneNode(true);
                iframe.appendChild(clon);
                console.log("Received Node  ++++++++++");
                console.log(clon);
                //iframe.srcdoc = clon;
                //document.body.appendChild(clon);
            </script>
        </div>
    </div>
</div>
