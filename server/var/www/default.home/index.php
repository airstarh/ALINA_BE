<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

####################################################################################################

$v   = ['a','b','c'];
$res = json_decode(json_encode($v), false);

####################################################################################################
echo '<pre>';
echo PHP_EOL;
echo PHP_EOL;
var_export($res, 0);
echo PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
echo "is_object: " . (is_object($res) ? 'yes' : 'no') . PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
echo "is_array:  " . (is_array($res) ? 'yes' : 'no') . PHP_EOL;
echo PHP_EOL;
echo PHP_EOL;
