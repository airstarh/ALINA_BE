<?php
return [
    'appNamespace' => 'm45a',
    'title'        => 'Миронова 45А',
    'description'  => 'Официальный сайт ТСН "ТСЖ Миронова 45 А"',
    'logVisitsToDb'       => TRUE,
    'db'                  => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
    'mvc'          => [
        'defaultController'      => 'Main',
        'defaultAction'          => 'index',
        'pageNotFoundController' => 'Main',
        'pageNotFoundAction'     => '404',
        'structure'              => [],
    ],
];
