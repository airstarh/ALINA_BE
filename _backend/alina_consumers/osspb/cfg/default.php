<?php
return [
    'appNamespace' => 'osspb',
    'title'        => 'Отдел Сервис СПб',
    'description'  => '',
    'logVisitsToDb'       => FALSE,
    'db'                  => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
    'mvc'          => [
        'defaultController'      => 'main',
        'defaultAction'          => 'index',
        'pageNotFoundController' => 'main',
        'pageNotFoundAction'     => '404',
        'structure'              => [],
    ],
];
