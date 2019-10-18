<?php
/** @var $data stdClass */

use alina\utils\Html as HtmlAlias;

?>
<div>
    <table class="table">
        <thead class="thead-dark">
        <tr>
            <th>LINK</th>
            <th>DESCRIPTION</th>
        </tr>
        </thead>
        <tbody>
        <!--region Prototype -->
        <!--        <tr>-->
        <!--            <th class="LINK"><a href="." target="_blank"></th>-->
        <!--            <th class="DESC"></th>-->
        <!--        </tr>-->
        <!--endregion Prototype -->
        <?php foreach ($data as $l => $t) { ?>
            <tr>
                <td colspan="2">
                    <h2><?= $t ?></h2>
                </td>
            </tr>
            <tr>
                <td>
                    <a href="<?= $l ?>" target="_blank">
                        <?= $t ?>
                    </a>
                </td>
                <td>
                    <a href="<?= $l ?>" target="_blank">
                        <?= $l ?>
                    </a>
                    <br>
                    <br>
                    <a href="<?= HtmlAlias::aRef($l) ?>" target="_blank">
                        <?= HtmlAlias::aRef($l) ?>
                    </a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
