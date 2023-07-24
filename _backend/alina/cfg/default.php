<?php
return [
    'appNamespace'        => 'alina',
    'title'               => 'Alina: another PHP framework. Powered by OrcTechService.',
    'fileUploadDir'       => ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'uploads',
    'logVisitsToDb'       => TRUE,
    'db'                  => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
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
    'forceSysPathToAlias' => TRUE,
    'vocAliasUrl'         => [
        'sitemap.xml'                 => 'sitemap',
        'действие/:p2/контроллер/:p1' => 'cont/act/:p1/:p2', // /действие/ВТОРОЙ_ПАРАМЕТР/контроллер/ПЕРВЫЙ_ПАРАМЕТР
        'Пользователь/Логин'          => 'Auth/Login',
        'Регистрация'                 => 'Auth/Register',
        'Рест_Запрос'                 => 'alinaRestAccept/index',
    ],
    'debug'               => [
        'toPage' => TRUE,
        'toDb'   => TRUE,
        'toFile' => FALSE,
    ],
    'watcher'             => [
        'maxPer1sec'          => 20,
        'maxPer10secs'        => 10 / 5 * 20,
        'maxPer1min'          => 60 / 2 * 20,
        'maxPer10mins'        => 60 * 60 / 2 * 20,
        'classDataFiltration' => '',
        'fileUpload'          => [
            'max' => [
                'registered' => 100,
                'admin'      => -1,
                'moderator'  => -1,
                'privileged' => -1,
            ],
        ],
        'newTale'             => [
            'max' => [
                'registered' => 3,
                'admin'      => -1,
                'moderator'  => -1,
                'privileged' => 10,
            ],
        ],
    ],
    'mailer'              => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'mailer.php'),
    'html'                => [
        'css'  => [
            // Jquery; Jquery UI
            'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css',
            //Bootstrap Framework.
            'https://stackpath.bootstrapcdn.com/bootstrap/latest/css/bootstrap.min.css',
            // Ckeditor
            //@see https://ckeditor.com/docs/ckeditor5/latest/builds/guides/integration/content-styles.html#sharing-content-styles-between-frontend-and-backend
            '/sources/css/ckeditor.css',
            '/sources/css/ckeditor-theme.css',
            // Alina
            '/sources/css/alina-vars.css',
            '/sources/css/alina.css',
            '/sources/css/alina-app-specific.css',
        ],
        'js'   => [
            // Jquery; Jquery UI
            'https://code.jquery.com/jquery-3.4.1.js',
            'https://code.jquery.com/ui/1.12.1/jquery-ui.js',
            //Bootstrap JS
            'https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js',
            'https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js',
            // Alina.
            '/sources/js/alina-js-collector.php',
        ],
        'meta' => [],
    ],
    'ui'                  => [
        'domain' => 'http://127.0.0.1:8082',
    ],
];
