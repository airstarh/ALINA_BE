<?php
switch (ALINA_ENV) {
    case  'HOME':
    case  'DA':
    default:
        return [
            'appNamespace'        => 'alina',
            'title'               => 'Alina: another PHP framework. Powered by OrcTechService.',
            'db'                  => [
                'driver'    => 'mysql',
                'host'      => 'localhost',
                'database'  => 'alina',
                'username'  => 'root',
                'password'  => '',
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => '',
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
            'forceSysPathToAlias' => TRUE,
            'vocAliasUrl'         => [
                'действие/:p2/контроллер/:p1' => 'cont/act/:p1/:p2', // /действие/ВТОРОЙ_ПАРАМЕТР/контроллер/ПЕРВЫЙ_ПАРАМЕТР
            ],

            'debug' => [
                //'toPage' => TRUE,
                //'toDb'   => TRUE,
                'toFile' => TRUE,
            ],

            'html' => [

                'css'  => [
	                // Jquery; Jquery UI
                	'/sources/node_modules/jquery-ui/themes/base/all.css',

	                //Bootstrap Framework.
	                '/sources/node_modules/bootstrap/dist/css/bootstrap.min.css',
	                '/sources/node_modules/bootstrap/dist/css/bootstrap-theme.min.css',

	                // Alina
                    '/sources/css/alina.css',
                    //'/frontend/css/alina_form.css',
                ],

                'js'   => [
	                // Jquery; Jquery UI
	                '/sources/node_modules/jquery/dist/jquery.min.js',
	                '/sources/node_modules/jquery-ui/external/requirejs/require.js',

	                //Bootstrap Framework.
	                '/sources/node_modules/bootstrap/dist/js/bootstrap.min.js',

                    // Alina.
                    '/sources/js/001_alina_init.js',
                    //'/sources/js/002_alina_hash_catcher.js',
                ],
                'meta' => [],
            ],
        ];
        break;
}