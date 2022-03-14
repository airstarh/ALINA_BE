<?php
return [
    'appNamespace' => 'vov',
    'title'        => 'Осетровский плацдарм',
    'description'  => 'Мемориальный комплекс "Осетровский плацдарм"  Острогожского историко-художественного музея им. И.Н. Крамского',
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
