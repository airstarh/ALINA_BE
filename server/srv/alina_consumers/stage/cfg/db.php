<?php
return [
    'driver'    => 'mysql',
    'host'      => 'localhost',
    'database'  => 'xxx',
    'username'  => 'root',
    'password'  => getenv('MYSQL_ROOT_PASSWORD'),
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
    'port'      => 3306,
];
