<div id="SendRestApiQueries">
    <h1>Query Parameters</h1>
    <div>
        <form action="." method="post" enctype="multipart/form-data">

            <div>
                <button type="submit" class="btn btn-lg btn-primary">Submit!</button>
                <a href="." class="btn btn-lg btn-danger">RESET</a>
            </div>

            <div>
                <label>reqUri</label>
                <input type="text" name="reqUri" value="<?= $data->reqUri ?>" class="form-control">
            </div>
            <div>
                <label>reqHeaders</label>
                <textarea name="reqHeaders" class="form-control" rows="5"><?= hlpGetBeautifulJsonString($data->reqHeaders) ?></textarea>
            </div>
            <div>
                <label>reqGet</label>
                <textarea name="reqGet" class="form-control" rows="5"><?= hlpGetBeautifulJsonString($data->reqGet) ?></textarea>
            </div>
            <div>
                <label>reqPost</label>
                <textarea name="reqPost" class="form-control" rows="5"><?= hlpGetBeautifulJsonString($data->reqPost) ?></textarea>
            </div>
            <div>
                <button type="submit" class="btn btn-lg btn-primary">Submit!</button>
                <a href="." class="btn btn-lg btn-danger">RESET</a>
            </div>
        </form>
    </div>
    <hr>
    <h1>Results</h1>
    <input type="text" value="<?= $data->q->resultedUri ?>" class="form-control">
    <iframe src="<?= $data->q->resultedUri ?>" class="w-100"></iframe>
    <h2>$data->q->responseBody</h2>
    <textarea class="form-control w-100" rows="30"><?= $data->q->responseBody ?></textarea>

    <h2>$data->q->responseHeadersStructurized</h2>
    <textarea class="form-control w-100" rows="10"><?= hlpGetBeautifulJsonString($data->q->responseHeadersStructurized) ?></textarea>
</div>
