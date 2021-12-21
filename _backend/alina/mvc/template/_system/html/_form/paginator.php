<?php
/** @var $data stdClass */

use alina\utils\Data;
use alina\utils\Request;
use \alina\utils\Url;

$pageCurrentNumber = $data->pageCurrentNumber;
$pageSize          = $data->pageSize;
$pagesTotal        = $data->pagesTotal;
$rowsTotal         = $data->rowsTotal;
$paginationVersa   = $data->paginationVersa;
$arrPages          = range(1, $pagesTotal);
$path              = $data->path;
$flagHrefAsPath    = $data->flagHrefAsPath;
?>

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
    class="btn btn-sm m-1 <?= $p == $pageCurrentNumber ? 'btn-info' : 'btn-dark' ?>"
  ><?= $p ?></a>
<?php } ?>
