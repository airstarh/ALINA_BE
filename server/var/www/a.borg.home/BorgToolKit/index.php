<?php
const BORG_PATH = __DIR__;
$dirInc  = BORG_PATH . '/inc';
$listDir = scandir($dirInc);
foreach ($listDir as $f) {
    if ($f === '.' || $f === '..') continue;
    require_once "$dirInc/$f";
}
