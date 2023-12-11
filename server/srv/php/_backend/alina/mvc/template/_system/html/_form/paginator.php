<?php
/** @var $data stdClass */

use alina\Utils\Data;
use alina\Utils\Request;
use \alina\Utils\Url;

$pageCurrentNumber = $data->pageCurrentNumber;
$pageSize          = $data->pageSize;
$pagesTotal        = $data->pagesTotal;
$rowsTotal         = $data->rowsTotal;
$paginationVersa   = $data->paginationVersa;
$arrPages          = range(1, $pagesTotal);
$path              = $data->path;
$flagHrefAsPath    = $data->flagHrefAsPath;
if ($pagesTotal > 5) {
    $length   = count($arrPages);
    $n        = 10;
    $l        = 20;
    $i        = $pageCurrentNumber - 1;
    $cutFrom  = ($i - $n <= 0) ? 0 : $i - $n;
    $arrPages = array_slice($arrPages, $cutFrom, $l);
    //$arrPages = array_slice($arrPages, $pageCurrentNumber + 3, $length);
    //$arrPages = array_slice($arrPages, -6, -$length);
}
?>

<?php
if ($flagHrefAsPath) {
    ?>...<?php
}
else { ?>
  <a
    href="<?= Url::bizAddGetParamsToCurrentState('', ['p' => 1, 'ps' => $pageSize,]) ?>"
    class="btn btn-sm btn-outline-info m-1"
  > <<< </a>
  <a
    href="<?= Url::bizAddGetParamsToCurrentState('', ['p' => $pagesTotal, 'ps' => $pageSize,]) ?>"
    class="btn btn-sm btn-outline-info m-1"
  > >>> </a>
<?php } ?>

<?php
foreach ($arrPages as $p) {
    if ($flagHrefAsPath) {
        $href = "{$path}/{$pageSize}/{$p}";
    }
    else {
        $href = Url::bizAddGetParamsToCurrentState('', ['p' => $p, 'ps' => $pageSize,]);
    }
    ?>
  <a
    href="<?= $href ?>"
    class="btn btn-sm m-1 <?= $p == $pageCurrentNumber ? 'btn-info' : 'btn-outline-info' ?>"
  ><?= $p ?></a>
<?php } ?>
