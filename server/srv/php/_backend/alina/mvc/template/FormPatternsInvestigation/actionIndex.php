<?php
/** @var $data array */
?>

<div class="m-5 db-0">
    <form>
        <div class="form-group">
            <label for="exampleInputEmail1">Email address</label>
            <input type="email" class="form-control" id="exampleInputEmail1" aria-describedby="emailHelp">
            <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
        </div>
        <div class="form-group">
            <label for="exampleInputPassword1">Password</label>
            <input type="password" class="form-control" id="exampleInputPassword1">
        </div>
        <div class="form-group form-check">
            <input type="checkbox" class="form-check-input" id="exampleCheck1">
            <label class="form-check-label" for="exampleCheck1">Check me out</label>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>


<div class="m-5 db-1">
    <form>
        <div class="form-group">
            <label for="exampleFormControlInput1">Email address</label>
            <input type="email" class="form-control" id="exampleFormControlInput1" placeholder="name@example.com">
        </div>
        <div class="form-group">
            <label for="exampleFormControlSelect1">Example select</label>
            <select class="form-control" id="exampleFormControlSelect1">
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
            </select>
        </div>
        <div class="form-group">
            <label for="exampleFormControlSelect2">Example multiple select</label>
            <select multiple class="form-control" id="exampleFormControlSelect2">
                <option>1</option>
                <option>2</option>
                <option>3</option>
                <option>4</option>
                <option>5</option>
            </select>
        </div>
        <div class="form-group">
            <label for="exampleFormControlTextarea1">Example textarea</label>
            <textarea class="form-control" id="exampleFormControlTextarea1" rows="3"></textarea>
        </div>
    </form>
</div>

<div class="m-5 db-2">
    <form>
        <div class="form-group">
            <label for="formControlRange">Example Range input</label>
            <input type="range" class="form-control-range" id="formControlRange">
        </div>
    </form>
</div>


<div class="m-5 db-3">
    <form>
        <div class="form-group row">
            <label for="inputEmail3" class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-10">
                <input type="email" class="form-control" id="inputEmail3">
            </div>
        </div>
        <div class="form-group row">
            <label for="inputPassword3" class="col-sm-2 col-form-label">Password</label>
            <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword3">
            </div>
        </div>
        <fieldset class="form-group row">
            <legend class="col-form-label col-sm-2 float-sm-left pt-0">Radios</legend>
            <div class="col-sm-10">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios1" value="option1" checked>
                    <label class="form-check-label" for="gridRadios1">
                        First radio
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios2" value="option2">
                    <label class="form-check-label" for="gridRadios2">
                        Second radio
                    </label>
                </div>
                <div class="form-check disabled">
                    <input class="form-check-input" type="radio" name="gridRadios" id="gridRadios3" value="option3" disabled>
                    <label class="form-check-label" for="gridRadios3">
                        Third disabled radio
                    </label>
                </div>
            </div>
        </fieldset>
        <div class="form-group row">
            <div class="col-sm-10 offset-sm-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="gridCheck1">
                    <label class="form-check-label" for="gridCheck1">
                        Example checkbox
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-sm-10">
                <button type="submit" class="btn btn-primary">Sign in</button>
            </div>
        </div>
    </form>
</div>

<div class="m-5 db-4">
    <form>
        <div class="form-row align-items-center">
            <div class="col-auto">
                <label class="sr-only" for="inlineFormInput">Name</label>
                <input type="text" class="form-control mb-2" id="inlineFormInput" placeholder="Jane Doe">
            </div>
            <div class="col-auto">
                <label class="sr-only" for="inlineFormInputGroup">Username</label>
                <div class="input-group mb-2">
                    <div class="input-group-prepend">
                        <div class="input-group-text">@</div>
                    </div>
                    <input type="text" class="form-control" id="inlineFormInputGroup" placeholder="Username">
                </div>
            </div>
            <div class="col-auto">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="autoSizingCheck">
                    <label class="form-check-label" for="autoSizingCheck">
                        Remember me
                    </label>
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary mb-2">Submit</button>
            </div>
        </div>
    </form>
</div>

<div class="m-5 db-5">
    <div class="custom-control custom-radio custom-control-inline">
        <input type="radio" id="customRadioInline1" name="customRadioInline" class="custom-control-input">
        <label class="custom-control-label" for="customRadioInline1">Toggle this custom radio</label>
    </div>
    <div class="custom-control custom-radio custom-control-inline">
        <input type="radio" id="customRadioInline2" name="customRadioInline" class="custom-control-input">
        <label class="custom-control-label" for="customRadioInline2">Or toggle this other custom radio</label>
    </div>
</div>

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
