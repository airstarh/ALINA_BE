<div id="form-patterns-investigation">
    <div>
        <form action="." method="post" enctype="multipart/form-data">

            <div class="button">
                <button type="submit" class="btn btn-lg btn-primary">Submit!</button>
                <a href="." class="btn btn-lg btn-danger">RESET</a>
            </div>
            <div class="radio">
                <label>input radio</label>
                <input type="radio" name="radio[]" value="" class="form-control">
                <input type="radio" name="radio[]" value="" class="form-control">
                <input type="radio" name="radio[]" value="" class="form-control">
            </div>
            <div class="input">
                <label>input checkbox</label>
                <input type="checkbox" name="checkbox[a]" value="" class="form-control">
                <input type="checkbox" name="checkbox[b]" value="" class="form-control">
                <input type="checkbox" name="checkbox[c]" value="" class="form-control">
            </div>
            <div class="input">
                <label>input</label>
                <input type="text" name="input" value="" class="form-control">
            </div>
            <div class="textarea">
                <label>textarea</label>
                <textarea name="textarea" class="form-control" rows="10"></textarea>
            </div>
        </form>
        <div>
            <?php
            echo '<pre>';
            print_r($data);
            echo '</pre>';
            ?>
        </div>
    </div>
</div>
