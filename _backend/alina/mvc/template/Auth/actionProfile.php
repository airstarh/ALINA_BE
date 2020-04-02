<?php
/** @var $data stdClass */

use alina\mvc\model\CurrentUser as CurrentUserAlias;
use alina\mvc\view\html as htmlAlias;

$m = $data->user;
//$sources = $data->sources;
?>
<div>
    <div class="container">
        <div class="row">
            <div class="col"><h5 class="text-break">Profile #<?= $m->id ?></h5></div>
        </div><!---->
        <div class="text-break">
            <div class="row">
                <div class="col-4"><a href="<?= $m->emblem ?>" target="_blank"><img src="<?= $m->emblem ?>" width="100%"></a></div>
                <div class="col">
                    <div class="row mb-1 justify-content-center align-items-center">
                        <div class="notranslate col font-weight-bold">
                            <?= $m->firstname ?> <?= $m->lastname ?>
                        </div>
                    </div>
                    <div class="row mb-1 justify-content-center align-items-center">
                        <div class="col">
                            <?= $m->birth ?>
                        </div>
                    </div>
                    <div class="row mb-1 justify-content-center align-items-center">
                        <div class="col-12"><a href="mailto:vsevolod.azovsky@gmail.com">
                                <?= $m->mail ?>
                            </a></div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col">
                    <div class="ck-content">
                        <div class="notranslate">
                            <?= $m->about_myself ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
