<?php
switch (ALINA_ENV) {
    case  'HOME':
        return [
            'db'           => [
                'driver'    => 'mysql',
                'host'      => 'localhost',
                'database'  => 'database',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
            ],
            'appNamespace' => 'alina',
            'mvc'          => [
                'defaultController'       => 'root',
                'defaultAction'           => 'Index',
                'pageNotFoundController'  => 'root',
                'pageNotFoundAction'      => '404',
                'pageExceptionController' => 'root',
                'pageExceptionAction'     => 'Exception',
                'structure'               => [
                    'controller' => 'core\\mvc\\controller',
                    'model'      => 'core\\mvc\\model',
                    'view'       => 'core\\mvc\\view',
                    'template'   => 'core\\mvc\\template',
                ],
            ],

            'forceSysPathToAlias' => TRUE,
            'vocAliasUrl'         => [
                'действие/:p2/контроллер/:p1' => 'cont/act/:p1/:p2', // /действие/ВТОРОЙ_ПАРАМЕТР/контроллер/ПЕРВЫЙ_ПАРАМЕТР
            ],

            'debug' => [
                //'toPage' => TRUE,
                //'toDb'   => TRUE,
                'toFile' => TRUE,
            ],

            'page' => [
                // All templates are stored:
                'templateDir'           => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template',
                // Controller template directory name.
                'templateDirController' => '', // Is set by Controller's constructor.

                'template' => 'layout',
                'css'      => [
                    // jQ UI
                    'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/themes/smoothness/jquery-ui.css',
                    // Bootstrap
                    'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css',
                    'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css',
                    // elFinder
                    '/frontend/js/elfinder/css/elfinder.min.css',
                    '/frontend/js/elfinder/css/theme.css',
                    // Custom
                    '/frontend/css/form.css',
                    '/frontend/css/liga-custom.css',
                ],
                'js'       => [
                    // jQ
                    'https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js',
                    // jQ UI
                    'https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js',
                    // Bootstrap
                    'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js',
                    // Bootstrap 3rd part theme
                    'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js',

                    // elFinder
                    '/frontend/js/elfinder/js/elfinder.min.js',
                    '/frontend/js/elfinder/js/i18n/elfinder.ru.js',

                    // ckeditor
                    '/frontend/js/ckeditor/ckeditor.js',

                    //            '/frontend/js/PFBC/Resources/ckeditor/ckeditor.js',
                    //            '/frontend/js/PFBC/Resources/tiny_mce/tiny_mce.js',

                    // system
                    '/frontend/js/init.js',
                    '/frontend/js/hash-catcher.js',

                    // custom
                    '/frontend/js/liga-custom.js',
                ],
                'meta'     => [],
            ],
        ];
        break;
}