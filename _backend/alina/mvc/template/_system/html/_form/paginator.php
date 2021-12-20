<?php
/** @var $data stdClass */

use alina\utils\Data;
use alina\utils\Request;
use function addGetFromObject as addGetFromObjectAlias;

echo '<pre>';
var_export($data, 0);
echo '</pre>';
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
        $curGet = Request::obj()->GET;
        if (property_exists($curGet, 'alinapath')) {
            unset($curGet->alinapath);
        }
        $newGet = Data::mergeObjects(Request::obj()->GET, (object)['p' => $p, 'ps' => $pageSize,]);
        $href   = \alina\utils\Url::addGetFromObject($path, $newGet);
    }
    ?>
  <a
    href="<?= $href ?>"
    class="btn btn-sm btn-info"
  ><?= $href ?></a>
<?php } ?>
