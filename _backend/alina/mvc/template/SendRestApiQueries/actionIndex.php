<div class="row">
    <div class="col-sm">
        <form action="." method="post" enctype="multipart/form-data">
            <h2>Request</h2>
            <div class="form-group mt-3">
                <button type="submit" class="btn btn-lg btn-primary">Submit!</button>
                <a href="." class="btn btn-lg btn-danger">RESET</a>
            </div>

            <div class="form-group mt-3">
                <label>reqUri</label>
                <input type="text" name="reqUri" value="<?= $data->reqUri ?>" class="form-control">
            </div>
            <div class="form-group mt-3">
                <label>reqHeaders</label>
                <textarea name="reqHeaders" class="form-control"
                          rows="5"><?= hlpGetBeautifulJsonString($data->reqHeaders) ?></textarea>
            </div>
            <div class="form-group mt-3">
                <label>reqGet</label>
                <textarea name="reqGet" class="form-control"
                          rows="5"><?= hlpGetBeautifulJsonString($data->reqGet) ?></textarea>
            </div>


            <fieldset>POST
                <div class="form-group mt-3">
                    <label for="reqPost">reqPost</label>
                    <textarea name="reqPost" class="form-control" id="reqPost"
                              rows="5"><?= hlpGetBeautifulJsonString($data->reqPost) ?></textarea>
                </div>
                <div class="custom-control custom-checkbox">
                    <input name="reqPostRaw" <?= $data->reqPostRaw ? 'checked' : '' ?> class="custom-control-input"
                           type="checkbox" value="1" id="reqPostRaw">
                    <label class="custom-control-label" for="reqPostRaw">
                        reqPostRaw
                    </label>
                </div>
            </fieldset>


            <div class="form-group mt-3">
                <button type="submit" class="btn btn-lg btn-primary">Submit!</button>
                <a href="." class="btn btn-lg btn-danger">RESET</a>
            </div>
        </form>
    </div>
    <div class="col-sm">
        <div class="mt-3">
            <h2>Response</h2>
            <div class="mt-3">
                <button type="button" class="btn btn-primary">
                    URI <span class="badge badge-light">$data->q->resultedUri</span>
                </button>
                <input type="text" value="<?= $data->q->resultedUri ?>" class="form-control">
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-primary">
                    Body <span class="badge badge-light">$data->q->responseBody</span>
                </button>
                <textarea class="form-control w-100" rows="11"
                ><?= hlpGetBeautifulJsonString($data->q->responseBody) ?></textarea>
            </div>

            <div class="mt-3">
                <button type="button" class="btn btn-primary">
                  Headers  <span class="badge badge-light">$data->q->responseHeadersStructurized </span>
                </button>
                <textarea class="form-control w-100"
                          rows="11"
                ><?= hlpGetBeautifulJsonString($data->q->responseHeadersStructurized) ?></textarea>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm">
        <div class="m-5">
            <button type="button" class="btn btn-primary">
                iframe <span class="badge badge-light">$data->q->responseBody</span>
            </button>
            <iframe id="alina-dynamic-request-result" src="" class="w-100" height="500"></iframe>
            <template id="responseBody">
                <?= $data->q->responseBody ?>
            </template>
            <script type="text/javascript">
                var tpl = document.querySelector('#responseBody');
                //var iframe = document.querySelector('#alina-dynamic-request-result');
                var iframe = document.querySelector('#alina-dynamic-request-result').contentWindow.document.body;
                var clon = tpl.content.cloneNode(true);
                iframe.appendChild(clon);
                console.log("xxx ++++++++++");
                console.log(clon);
                //iframe.srcdoc = clon;
                //document.body.appendChild(clon);
            </script>
        </div>
    </div>
</div>
