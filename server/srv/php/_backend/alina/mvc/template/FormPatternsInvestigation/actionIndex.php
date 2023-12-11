<div id="form-patterns-investigation">
    <div>
        <form action="" method="post" enctype="multipart/form-data">

            <div class="mt-3">
                <label>Date</label>
                <input type="date" name="input" value="" class="datepicker form-control">
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-lg btn-primary">Submit!</button>
                <a href="." class="btn btn-lg btn-danger">RESET</a>
            </div>
            <div class="mt-3">
                <label>input radio</label>
                <input type="radio" name="radio[]" value="1" class="form-control">
                <input type="radio" name="radio[]" value="2" class="form-control">
                <input type="radio" name="radio[]" value="3" class="form-control">
            </div>
            <div class="mt-3">
                <label>input checkbox</label>
                <input type="checkbox" name="checkbox[a]" value="10" class="form-control">
                <input type="checkbox" name="checkbox[b]" value="20" class="form-control">
                <input type="checkbox" name="checkbox[c]" value="30" class="form-control">
            </div>
            <div class="mt-3">
                <label>input</label>
                <input type="text" name="input" value="" class="form-control">
            </div>



            <div class="mt-3">
                <label>textarea</label>
                <textarea name="textarea" class="form-control" rows="4"></textarea>
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
