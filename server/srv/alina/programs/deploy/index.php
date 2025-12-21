<?php

use alina\Utils\FS;
require_once __DIR__ . '/../../AppBoot.php';

$projectList = [
    # 'from' => 'to',
    #'/srv/alina_consumers/zero.home' => '/var/www/zero.home',
    '/srv/alina_consumers/zero.home/.WwwDiff' => '/srv/testCopySmart/sewa',
];

foreach ($projectList as $from => $to) {
    FS::copySmart($from, $to);
}