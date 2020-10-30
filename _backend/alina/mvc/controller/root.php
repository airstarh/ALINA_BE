<?php

namespace alina\mvc\controller;

use alina\Message;
use alina\utils\Request;

class root
{
    public function actionIndex()
    {
        require_once(ALINA_WEB_PATH . '/apps/vue/index.html');
    }

    public function actionIndex2()
    {
        $vd = (object)[
            '/AdminTests/Redirect1'                         => 'Redirect',
            '/AdminTests/somedata'                          => 'Some Data',
            '/AdminTests/ConversionToObject'                => 'Conversion to Object',
            '/AdminTests/BaseAlinaModel'                    => 'action BaseAlinaModel',
            '/AdminTests/ReversibleEncryption'              => 'Test Reversible Encryption',
            '/AdminTests/Mailer'                            => 'Test Mail Send',
            '/FileUpload/Common'                            => 'File Upload',
            '/main/index'                                   => 'ZERO',
            '/AdminTests/TestMessages'                      => 'Messages',
            '/Auth/Login'                                   => 'Auth Login',
            '/Auth/Profile'                                 => 'Auth User',
            '/Auth/ChangePassword'                          => 'Auth actionChangePassword',
            '/Auth/Register?lala=lala'                      => 'Auth Register',
            '/Auth/logout?lala=lala'                        => 'Auth Log Out',
            '/Auth/ResetPasswordRequest?lala=lala'          => 'Auth ResetPasswordRequest',
            '/Auth/ResetPasswordWithCode?lala=lala'         => 'Auth ResetPasswordWithCode',
            '/root/index?lalala=333'                        => 'Root with GET',
            '/egCookie/Test001'                             => 'COOKIE',
            '/FormPatternsInvestigation/index/'             => 'Form Patterns Investigation',
            '/AdminDbManager/EditRow/user/1'                => 'Edit a DB line',
            '/alinaRestAccept/index?cmd=model&m=user&mId=1' => 'Rest call',
            '/NotExistingPage'                              => 'Test 404',
            '/tools/SerializedDataEditor'                   => 'Serialized Data Editor',
            '/CtrlDataTransformations/json'                 => 'JSON search-replace',
            '/AdminDbManager/DbTablesColumnsInfo'           => 'MySQL Manager',
            '/SendRestApiQueries/BaseCurlCalls'             => 'HTTP calls',
            '/AdminTests/Errors'                            => 'Tst Errors',
            '/AdminTests/Serialization'                     => 'Tst Serialization',
            '/AdminTests/JsonEncode'                        => 'Tst Json Encode',
        ];

        error_log('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>', 0);
        error_log(__FUNCTION__, 0);
        error_log(var_export(Request::obj(), 1), 0);
        error_log('<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<', 0);

        echo (new \alina\mvc\view\html)->page($vd);
    }

    public function action404()
    {
        AlinaResponseSuccess(0);
        http_response_code(404);
        echo (new \alina\mvc\view\html)->page();
        exit;
    }

    public function actionException($vd = NULL)
    {
        AlinaResponseSuccess(0);
        http_response_code(500);
        echo (new \alina\mvc\view\html)->page($vd, '_system/html/htmlLayoutErrorCatcher.php');
        exit;
    }

    public function actionAccessDenied($code = 403)
    {
        AlinaResponseSuccess(0);
        http_response_code($code);
        echo (new \alina\mvc\view\html)->page(NULL, '_system/html/htmlLayoutErrorCatcher.php');
        exit;
    }
}
