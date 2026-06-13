<?php

return [
    'appNamespace'  => 'stage',
    'title'         => 'STAGE',
    'description'   => 'STAGE',
    'logVisitsToDb' => true,
    'db'            => require_once __DIR__ . DIRECTORY_SEPARATOR . 'db.php',
    'mvc'           => [
        'defaultController'      => 'Main',
        'defaultAction'          => 'index',
        'pageNotFoundController' => 'Main',
        'pageNotFoundAction'     => '404',
        'structure'              => [],
    ],
];
