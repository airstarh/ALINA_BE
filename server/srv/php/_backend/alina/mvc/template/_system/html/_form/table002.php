<?php /** @var $data stdClass | array */

use alina\mvc\View\html;
use alina\Utils\Data;

if (empty($data)) {
    return;
}

if (is_object($data)) $data = [$data];

$counter = 1;

$firstRow = current($data);
$headers  = array_keys((array)$firstRow);
?>
<div class="m-1">
    <table class="bg-black alina-data-table table-002">
        <thead>
        <tr>
            <th>#</th>
            <?php foreach ($headers as $h) { ?>
                <th><?= ___($h) ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>


        <?php foreach ($data as $k => $row) { ?>
            <tr>
                <td>

                    <?php if (!is_numeric($k)): ?>
                        <div>
                            <?= ___($k) ?>
                        </div>
                    <?php else: ?>
                        <?= $counter++ ?>
                    <?php endif; ?>
                </td>

                <?php if (!Data::isIterable($row)): ?>
                    <td>
                        <div><?= $k ?></div>
                        <div><?= $row ?></div>
                    </td>
                <?php else: ?>


                    <?php foreach ($row as $colName => $colValue) { ?>

                        <td>
                            <?php if (Data::isIterable($colValue)) { ?>
                                <div><?= $colName ?></div>
                                <?= (new html)->piece('_system/html/_form/table002.php', $colValue) ?>
                            <?php } else { ?>
                                <?= $colValue ?>
                            <?php } ?>
                        </td>
                    <?php } ?>

                <?php endif; ?>

            </tr>
        <?php } ?>

        </tbody>
    </table>
</div>