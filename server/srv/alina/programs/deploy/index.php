<?php

use alina\Utils\FS;
require_once __DIR__ . '/../../AppBoot.php';

$projectList = [
    // ZERO
    '/var/www/zero.home' => [
        '/srv/alina_consumers/zero.home/.WwwDiff',
    ],

    // SAYSIMSIM.RU
    '/var/www/saysimsim.ru' => [
        '/srv/alina_consumers/zero.home/.WwwDiff',
        '/srv/alina_consumers/saysimsim.ru/.WwwDiff',
    ],

    // vov
    '/var/www/vov' => [
        '/srv/alina_consumers/zero.home/.WwwDiff',
        '/srv/alina_consumers/vov/.WwwDiff',
    ],

    // m45a
    '/var/www/m45a' => [
        '/srv/alina_consumers/zero.home/.WwwDiff',
        '/srv/alina_consumers/m45a/.WwwDiff',
    ],
];

foreach ($projectList as $to => $wwwDiff) {
    foreach ($wwwDiff as $from) {
        FS::copySmart($from, $to, true);
    }
}
