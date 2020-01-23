<?php
switch (ALINA_ENV) {
    case  'HOME':
    case  'DA':
    default:
        return [
            'appNamespace'        => 'alina',
            'title'               => 'Alina: another PHP framework. Powered by OrcTechService.',
            'fileUploadDir'       => ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'uploads',
            'logVisitsToDb'       => TRUE,
            'db'                  => [
                'driver'    => 'mysql',
                'host'      => 'localhost',
                'database'  => 'alina',
                'username'  => 'root',
                'password'  => '1234',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'port'      => 3306,
            ],
            'mvc'                 => [
                'defaultController'       => 'root',
                'defaultAction'           => 'Index',
                'pageNotFoundController'  => 'root',
                'pageNotFoundAction'      => '404',
                'pageExceptionController' => 'root',
                'pageExceptionAction'     => 'Exception',
                // Relative Class Namespace Path.
                'structure'               => [
                    'controller' => 'mvc\controller',
                    'model'      => 'mvc\model',
                    'view'       => 'mvc\view',
                    'template'   => 'mvc\template',
                ],
            ],
            // Routes, Aliases.
            'forceSysPathToAlias' => FALSE,
            'vocAliasUrl'         => [
                'действие/:p2/контроллер/:p1' => 'cont/act/:p1/:p2', // /действие/ВТОРОЙ_ПАРАМЕТР/контроллер/ПЕРВЫЙ_ПАРАМЕТР
                'Пользователь/Логин'          => 'Auth/Login',
                'Регистрация'                 => 'Auth/Register',
                'Рест_Запрос'                 => 'alinaRestAccept/index',
            ],
            'debug'   => [
                'toPage' => TRUE,
                'toDb'   => TRUE,
                'toFile' => TRUE,
            ],
            'watcher' => [
                'maxPer1sec'          => 20,
                'maxPer10secs'        => 10 / 5 * 20,
                'maxPer1min'          => 60 / 2 * 20,
                'maxPer10mins'        => 60 * 60 / 2 * 20,
                'classDataFiltration' => '',
            ],
            'mailer'  => [
                'admin' => [
                    'Host'     => 'smtp.yandex.ru',
                    'Port'     => 587,
                    'Username' => 'my-customer-mailbox@yandex.ru',
                    'Password' => 'qwerty123qwerty',
                    'FromName' => 'Alina service',
                ],
            ],
            'html'    => [
                'css' => [
                    // Jquery; Jquery UI
                    'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
                    //Bootstrap Framework.
                    // https://getbootstrap.com/docs/4.3/getting-started/introduction/
                    'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css',
                    //'/sources/node_modules/bootstrap/dist/css/bootstrap-theme.min.css',
                    // Alina
                    '/sources/css/alina.css',
                    '/sources/css/alina_form.css',
                ],
                'js'   => [
                    // Jquery; Jquery UI
                    // https://code.jquery.com/
                    // https://code.jquery.com/ui/
                    'https://code.jquery.com/jquery-3.4.1.js',
                    'https://code.jquery.com/ui/1.12.1/jquery-ui.js',
                    //Bootstrap Framework.
                    // https://getbootstrap.com/docs/4.3/getting-started/introduction/
                    'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js',
                    'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js',
                    // Alina.
                    //'/sources/js/001_alina_init.js',
                    //'/sources/js/002_alina_hash_catcher.js',
                    //'/sources/js/100_alina_exe.js',
                    '/sources/js/alina-js-collector.php',
                ],
                'meta' => [],
            ],
        ];
        break;
}
