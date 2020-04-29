<?php

namespace alina\mvc\controller;

use alina\Message;

class root
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function actionIndex2()
    {
        $vd = (object)[
            '/AdminTests/Redirect1'                                => 'Redirect',
            '/AdminTests/somedata'                                 => 'Some Data',
            '/AdminTests/ConversionToObject'                       => 'Conversion to Object',
            '/AdminTests/BaseAlinaModel'                           => 'action BaseAlinaModel',
            '/AdminTests/ReversibleEncryption'                     => 'Test Reversible Encryption',
            '/AdminTests/Mailer'                                   => 'Test Mail Send',
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
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new \alina\mvc\view\html)->page();
    }

    public function actionException($vd = NULL)
    {
        AlinaResponseSuccess(0);
        http_response_code(500);
        echo (new \alina\mvc\view\html)->page($vd, '_system/html/htmlLayoutErrorCatcher.php');

        return TRUE;
    }
}
