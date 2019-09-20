<?php /** @var $data stdClass */ ?>
<?php
$arr     = $data->arr;
$counter = 1;
/*
 * Example:
 * $arr = [
    'rowHeader' => [
        'colHeader' => 'Their Value',
    ],
];*/
?>
<div>
    <table class="table table-responsive table-striped table-hover">
        <tbody>
        <?php foreach ($arr as $rowHeader => $colInfo) { ?>
            <tr>
                <td><?= $counter ?></td>
                <?php foreach ($colInfo as $colHeader => $v) { ?>
                    <td>
                        <?= $v ?>
                    </td>
                <?php } ?>
            </tr>
            <?php $counter++ ?>
        <?php } ?>
        </tbody>
    </table>

</div>
