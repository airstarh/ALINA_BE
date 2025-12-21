<?php

use alina\Utils\FS;
require_once __DIR__ . '/../../AppBoot.php';

$projectList = [
    # 'from' => 'to',
    '/srv/alina_consumers/zero.home/.WwwDiff' => '/var/www/zero.home',
];

foreach ($projectList as $from => $to) {
    FS::copySmart($from, $to, true);
}