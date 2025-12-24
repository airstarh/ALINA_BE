<?php
return [
    'appNamespace'        => 'alina',
    'title'               => 'Alina: another PHP framework. Powered by BorgTechService.',
    'fileUploadDir'       => ALINA_WEB_PATH . DIRECTORY_SEPARATOR . 'uploads',
    'logVisitsToDb'       => true,
    'db'                  => require_once(__DIR__ . DIRECTORY_SEPARATOR . 'db.php'),
    'mvc'                 => [
        'defaultController'       => 'Root',
        'defaultAction'           => 'Index',
        'pageNotFoundController'  => 'Root',
        'pageNotFoundAction'      => '404',
        'pageExceptionController' => 'Root',
        'pageExceptionAction'     => 'Exception',
        // Relative Class Namespace Path.
        'structure'               => [
            'controller' => 'mvc\Controller',
            'Model'      => 'mvc\Model',
            'View'       => 'mvc\View',
            'template'   => 'mvc\template',
        ],
    ],
    // Routes, Aliases.
    'forceSysPathToAlias' => true,
    'vocAliasUrl'         => [
        'sitemap.xml'                 => 'sitemap',
        'действие/:p2/контроллер/:p1' => 'cont/act/:p1/:p2', // /действие/ВТОРОЙ_ПАРАМЕТР/контроллер/ПЕРВЫЙ_ПАРАМЕТР
        'Рест_Запрос'                 => 'alinaRestAccept/index',
    ],
    'debug'               => [
        'toPage' => true,
        'toDb'   => true,
        'toFile' => true,
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
            '/kiss/their.bootstrap/jquery-ui.css',

            //Bootstrap Framework.
            '/kiss/their.bootstrap/bootstrap.css',

            // Ckeditor
            //@see https://ckeditor.com/docs/ckeditor5/latest/builds/guides/integration/content-styles.html#sharing-content-styles-between-frontend-and-backend
            '/kiss/their.ck/index.css',

            // Alina
            '/kiss/alina.css/index.css',
            '/kiss/alina.css.specific/index.css',

        ],
        'js'   => [
            // Jquery; Jquery UI
            '/kiss/their.bootstrap/jquery.js',
            '/kiss/their.bootstrap/jquery-ui.js',

            //Bootstrap JS
            '/kiss/their.bootstrap/popper.js',
            '/kiss/their.bootstrap/bootstrap.js',

            // Alina.
            '/kiss/alina.js/alina-js-collector.php',
        ],
        'meta' => [],
    ],
    'frontend'            => [
        'path'                  => '/root/frontend',
        'login'                 => '/#/auth/login',
        'register'              => '/#/auth/register',
        'profile'               => '/#/auth/profile',
        'resetPasswordRequest'  => '/#/auth/reset_password_request',
        'resetPasswordWithCode' => '/#/auth/reset_password_with_code',
        'changePassword'        => '/#/auth/change_password',
        'feed'                  => '/#/tale/feed',
        'taleUpsert'            => '/#/tale/upsert',
        'taleNew'               => '/#/tale/new',
    ],
];
