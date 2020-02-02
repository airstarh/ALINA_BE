<?php
/** @var $data stdClass */

use alina\mvc\model\CurrentUser as CurrentUserAlias;
use alina\mvc\view\html as htmlAlias;

$m       = $data->user;
$sources = $data->sources;
?>
<h1 class="mt-3">Profile for <?= $m->mail ?></h1>
<div>
    <?php if (FALSE/*CurrentUserAlias::obj()->isAdmin()*/) { ?>
        <?= htmlAlias::elForm([
            'action'  => '',
            'enctype' => 'multipart/form-data',
            'model'   => $m,
            'sources' => $sources,
        ]) ?>
    <?php } else { ?>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="hidden" name="form_id" value="<?= $data->form_id ?>">
        <input type="hidden" name="id" value="<?= $m->id ?>">
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'mail';
            $fNameHuman = 'E-mail (login)';
            $disabled   = 'disabled';
            $value      = $m->mail;
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <input type="text" id="<?= $fName ?>" name="<?= $fName ?>" value="<?= $value ?>" class="form-control" <?= $disabled ?>>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'firstname';
            $fName      = 'firstname';
            $fNameHuman = 'First Name';
            $disabled   = '';
            $value      = $m->firstname;
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <input type="text" id="<?= $fName ?>" name="<?= $fName ?>" value="<?= $value ?>" class="form-control" <?= $disabled ?>>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'lastname';
            $fNameHuman = 'Last Name';
            $disabled   = '';
            $value      = $m->lastname;
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <input type="text" id="<?= $fName ?>" name="<?= $fName ?>" value="<?= $value ?>" class="form-control" <?= $disabled ?>>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'birth';
            $fNameHuman = 'Bith Date';
            $disabled   = '';
            $value      = $m->{$fName};
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>_<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <input data-altfield="<?= $fName ?>" type="text" id="<?= $fName ?>_<?= $fName ?>" name="<?= $fName ?>_<?= $fName ?>" value="<?= $value ?>" class="js-datepicker form-control" <?= $disabled ?>>
                <input type="hidden" id="<?= $fName ?>" name="<?= $fName ?>" class="form-control" <?= $disabled ?>>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'language';
            $fNameHuman = 'Language';
            $disabled   = '';
            $value      = $m->language;
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <input type="text" id="<?= $fName ?>" name="<?= $fName ?>" value="<?= $value ?>" class="form-control" <?= $disabled ?>>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'timezone';
            $fNameHuman = 'Time Zone';
            $disabled   = '';
            $value      = $m->timezone;
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <input type="text" id="<?= $fName ?>" name="<?= $fName ?>" value="<?= $value ?>" class="form-control" <?= $disabled ?>>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <div class="row mt-4 justify-content-center align-items-center">
            <?php
            $fName      = 'about_myself';
            $fNameHuman = 'About';
            $disabled   = '';
            $value      = $m->about_myself;
            ?>
            <div class="col-6 text-right">
                <label for="<?= $fName ?>">
                    <?= $fNameHuman ?>
                </label>
            </div>
            <div class="col-6">
                <textarea name="<?= $fName ?>" id="<?= $fName ?>" rows="10" class="form-control" <?= $disabled ?>><?= $value ?></textarea>
            </div>
        </div>
        <!--##################################################-->
        <!--##################################################-->
        <!--##################################################-->
        <?= htmlAlias::elFormStandardButtons() ?>
    </form>

</div>
<?php } ?>
