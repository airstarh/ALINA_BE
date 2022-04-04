<?php
return [
    'appNamespace' => 'm45a',
    'title'        => 'Миронова 45А. Официальный сайт',
    'description'  => 'Официальный сайт ТСН "ТСЖ Миронова 45 А"',
    'logVisitsToDb'       => TRUE,
    'db'                  => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
    'mvc'          => [
        'defaultController'      => 'main',
        'defaultAction'          => 'index',
        'pageNotFoundController' => 'main',
        'pageNotFoundAction'     => '404',
        'structure'              => [],
    ],
];
