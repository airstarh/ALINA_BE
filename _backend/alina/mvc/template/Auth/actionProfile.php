<?php
/** @var $data stdClass */

use alina\mvc\view\html as htmlAlias;

?>
<h1>Profile for <?= $data->user->collection[0]->mail ?></h1>

<?= htmlAlias::elForm([
    'action'  => '',
    'enctype' => 'multipart/form-data',
    'source'  => $data->user->collection[0],
]) ?>


<!--<form action="" method="post" enctype="multipart/form-data">-->
<!--    --><?php //foreach ($data->user->collection[0] as $f => $v) { ?>
<!--        <div>-->
<!--            --><?//= $f ?><!-- :-->
<!--            --><?php
//             echo '<pre>';
//             print_r($v);
//             echo '</pre>';
//             ?>
<!--        </div>-->
<!--    --><?php //} ?>
<!--</form>-->
