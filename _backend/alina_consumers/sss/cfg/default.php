<?php
return [
    'appNamespace'  => 'sss',
    'title'         => '¯\_(ツ)_/¯',
    'description'   => 'Коллектив индивидуалистовю. Идейная синь. Славные ублюдки. lucky fucky',
    'logVisitsToDb' => TRUE,
    'db'            => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
    'mvc'           => [
        'defaultController'      => 'main',
        'defaultAction'          => 'index',
        'pageNotFoundController' => 'main',
        'pageNotFoundAction'     => '404',
        'structure'              => [],
    ],
];
