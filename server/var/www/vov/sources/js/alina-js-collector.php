<?php
header('Content-Type: application/javascript');
$js = [
    __DIR__ . '/001_alina_init.js',
    __DIR__ . '/100_alina_exe.js',
];
foreach ($js as $f) {
    require_once($f);
}
