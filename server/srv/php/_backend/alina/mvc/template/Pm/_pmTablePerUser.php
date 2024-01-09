<?php /** @var $data stdClass | array */

use alina\mvc\View\html;
use alina\Utils\Data;
use alina\Utils\Str;

if (empty($data)) {
    return;
}

if (is_object($data)) $data = [$data];

$counter = 1;

//$firstRow = reset($data);
//$headers  = array_keys((array)$firstRow);
$GET            = \alina\Utils\Request::obj()->GET;
$prevRow        = [];
$wd_assignee_id = null;
$headers        = [];
$classArchived  = [];
foreach ($data as $iR => &$r) {
    foreach ($r as $f => &$v) {
        $headers[$f] = $f;
        switch ($f) {
            case 'wd_assignee_id':
                if ($f) $wd_assignee_id = $v;
                break;
            case 'wd_flag_archived':
                $classArchived[$iR] = $v === 1 ? 'archived' : 'notarchived';
                break;
            default:
                break;
        }
    }
}
?>
<?php
AlinaDebugJson($data);
?>
<div class="mt-2 mb-5">
    <table class="bg-black alina-data-table alina-table-stick-header w-pct-100">


        <thead>
        <tr>
            <th>#</th>
            <?php foreach ($headers as $h) { ?>

                <th>
                    <?php switch ($h): ?>
<?php case 'wd_id': ?>
                            <div>
                                <form action="" method="post">
                                    <input type="hidden" name="form_id" value="doArchiveAll">
                                    <input type="hidden" name="do" value="doArchiveAll">
                                    <input type="hidden" name="date_start" value="<?= $GET->date_start ?>">
                                    <input type="hidden" name="date_end" value="<?= $GET->date_end ?>">
                                    <input type="hidden" name="wd_assignee_id" value="<?= $wd_assignee_id ?>">
                                    <button class="btn btn-sm btn-warning m-1"
                                            type="submit"><?= ___('Archive All') ?></button>
                                </form>
                            </div>
                            <?php break; ?>
                        <?php default: ?>
                            <?= ___($h) ?>
                            <?php break; ?>
                        <?php endswitch; ?>
                </th>

            <?php } ?>
        </tr>
        </thead>


        <tfoot>
        <?php foreach ($data as $iRow => $vRow): ?>
            <?php if (!is_numeric($iRow)): ?>
                <tr>
                    <th><?= ___($iRow) ?></th>
                    <?php foreach ($vRow as $iF => $vF): ?>

                        <th><?= $vF ?></th>

                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        <tr>
            <th>#</th>
            <?php foreach ($headers as $h) { ?>
                <th><?= ___($h) ?></th>
            <?php } ?>
        </tr>
        </tfoot>

        <!--####################################################################################################-->
        <tbody>
        <?php foreach ($data as $idxRow => $row) { ?>
            <?php if (!is_numeric($idxRow)) continue; ?>
            <tr class="<?= $classArchived[$idxRow] ?>">
                <td>
                    <?php if (!is_numeric($idxRow)): ?>
                        <div>
                            <?= ___($idxRow) ?>
                        </div>
                    <?php else: ?>
                        <div><?= $counter++ ?></div>
                    <?php endif; ?>
                </td>

                <?php if (!Data::isIterable($row)): ?>
                    <td>
                        <div><?= $idxRow ?></div>
                        <div><?= $row ?></div>
                    </td>
                <?php else: ?>


                    <?php foreach ($row as $colName => $colValue) { ?>

                        <td class="
                                <?= Str::ifContains($colName, 'date') ? 'text-nowrap' : '' ?>
                            "
                        >
                            <?php if (Data::isIterable($colValue)) { ?>
                                <div><?= $colName ?></div>
                                <?= (new html)->piece('_system/html/_form/table002.php', $colValue) ?>
                            <?php } else { ?>

                                <?php if (
                                    isset($prevRow[$colName])
                                    && !is_numeric($prevRow[$colName])
                                    && !Str::ifContains($colName, 'date')
                                    && $prevRow[$colName] === $colValue
                                ): ?>

                                <?php elseif ($colName === 'wd_flag_archived'): ?>
                                    <?= $classArchived[$idxRow] ?>
                                <?php elseif ($colName === 'wd_id'): ?>
                                    <div>
                                        <form action="" method="post">
                                            <input type="hidden" name="form_id" value="doArchive">
                                            <input type="hidden" name="do" value="doArchive">
                                            <input type="hidden" name="wd_id" value="<?= $row['wd_id'] ?>">
                                            <button class="btn btn-sm btn-warning"
                                                    type="submit"><?= ___('Archive') ?></button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <?= $colValue ?>
                                <?php endif; ?>

                            <?php } ?>
                        </td>


                    <?php } ?>
                <?php endif; ?>
            </tr>

            <?php
            $prevRow = (array)$row;
            ?>
        <?php } ?>

        </tbody>
        <!--####################################################################################################-->
    </table>
</div>