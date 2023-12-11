<?php /** @var $data stdClass | array */ ?>
<?php
$counter = 1;
$headers = array_keys((array)$data[0]);
?>
<div>
    <table class="table table-responsive table-striped table-hover  table-dark">
        <thead>
        <tr>
            <td>Counter</td>
            <?php foreach ($headers as $h) { ?>
                <td><?= $h ?></td>
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
