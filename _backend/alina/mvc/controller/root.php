<?php

namespace alina\mvc\controller;

use alina\Message;

class root
{
    public function actionIndex()
    {
        $vd = (object)[
            '/FileUpload/Common'                                   => 'File Upload',
            '/main/index'                                          => 'ZERO',
            '/AdminTests/TestMessages'                             => 'Messages',
            '/Auth/Login'                                          => 'Login',
            '/Auth/Profile'                                        => 'User',
            '/Auth/Register?lala=lala'                             => 'Register',
            '/Auth/logout?lala=lala'                               => 'Log Out',
            '/root/index?lalala=333'                               => 'Root with GET',
            '/egCookie/Test001'                                    => 'COOKIE',
            '/FormPatternsInvestigation/index/'                    => 'Form Patterns Investigation',
            '/AdminDbManager/EditRow/user/1'                       => 'Edit a DB line',
            '/alinaRestAccept/index?cmd=model&m=user&mId=1'        => 'Rest call',
            '/NotExistingPage'                                     => 'Test 404',
            '/CtrlDataTransformations/SerializedArrayModification' => 'Unserialize-> replace -> Serialize',
            '/CtrlDataTransformations/json'                        => 'JSON search-replace',
            '/AdminDbManager/DbTablesColumnsInfo'                  => 'MySQL Manager',
            '/SendRestApiQueries/BaseCurlCalls'                    => 'HTTP calls',
            '/AdminTests/Errors'                                   => 'Tst Errors',
            '/AdminTests/Serialization'                            => 'Tst Serialization',
            '/AdminTests/JsonEncode'                               => 'Tst Json Encode',
        ];
        echo (new \alina\mvc\view\html)->page($vd);
    }

    public function action404()
    {
        http_response_code(404);
        echo (new \alina\mvc\view\html)->page();
    }

    public function actionException()
    {
//        if (\alina\utils\Sys::isAjax()) {
//            echo \alina\Message::returnAllMessages();
//
//            return TRUE;
//        }
        http_response_code(500);
        Message::set('Keep calm and try something else, while we fix the bug ¯\_(ツ)_/¯');
        echo (new \alina\mvc\view\html)->page('', '_system/html/htmlLayoutErrorCatcher.php');

        return TRUE;
    }
}
