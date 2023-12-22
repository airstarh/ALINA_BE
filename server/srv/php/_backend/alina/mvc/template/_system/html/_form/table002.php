<?php /** @var $data stdClass | array */
if (empty($data)) return;
$counter  = 1;
$firstRow = $data[0];
$headers  = array_keys((array)$firstRow);
?>
<div class="text-nowrap">
    <table class="bg-black alina-data-table">
        <thead>
        <tr>
            <th>#</th>
            <?php foreach ($headers as $h) { ?>
                <th><?= $h ?></th>
            <?php } ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $row) { ?>
            <tr>
                <td><?= $counter++ ?></td>
                <?php foreach ($row as $colName => $colValue) { ?>
                    <td><?= $colValue ?></td>
                <?php } ?>
            </tr>
        <?php } ?>

        </tbody>
    </table>
</div>
