<?php /** @var $data stdClass | array */

use alina\mvc\View\html;
use alina\Utils\Data;

if (is_object($data)) $data = [$data];
if (empty($data)) return;
$counter = 1;

$firstRow = $data[0];
$headers  = array_keys((array)$firstRow);
?>
<div class="text-nowrap bg-black p-1 m-1">
    <table class="bg-black alina-data-table">
        <thead>
        <tr>
            <th>#</th>
            <?php foreach ($headers as $h) { ?>
                <th><?= ___($h) ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row) { ?>
            <tr>
                <td><?= $counter++ ?></td>
                <?php foreach ($row as $colName => $colValue) { ?>
                    <td>
                        <?php if (Data::isIterable($colValue)) { ?>
                            <?= (new html)->piece('_system/html/_form/table002.php', $colValue) ?>
                        <?php } else { ?>
                            <?= $colValue ?>
                        <?php } ?>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>

        </tbody>
    </table>
</div>
