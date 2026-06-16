<?php

namespace alina\mvc\Controller;

use alina\mvc\View\html;

class Root
{
    /**
     * Summary of actionIndex
     * @return void
     */
    public function actionIndex()
    {
        require_once ALINA_WEB_PATH . '/apps/vue/index.html';
    }

    public function actionFrontend()
    {
        require_once ALINA_WEB_PATH . '/apps/vue/index.html';
    }

    public function actionIndex2()
    {
        $vd = (object) [
            '/main/CheckAutoload/qq/aa?Par1=ASD&Par2=HelloWorld' => 'Check Custom Zero Class',
            '/main/CheckAutoload'                                => 'CLEAN Check Custom Zero Class ',
            '/AdminTests/Redirect1'                              => 'Redirect',
            '/AdminTests/somedata'                               => 'Some Data',
            '/AdminTests/ConversionToObject'                     => 'Conversion to Object',
            '/AdminTests/BaseAlinaModel'                         => 'action BaseAlinaModel',
            '/AdminTests/ReversibleEncryption'                   => 'Test Reversible Encryption',
            '/AdminTests/Mailer'                                 => 'Test Mail Send',
            '/FileUpload/Common'                                 => 'File Upload',
            '/main/index'                                        => 'ZERO',
            '/AdminTests/TestMessages'                           => 'Messages',
            '/Auth/Login'                                        => 'Auth Login',
            '/Auth/Profile'                                      => 'Auth User',
            '/Auth/ChangePassword'                               => 'Auth actionChangePassword',
            '/Auth/Register?lala=lala'                           => 'Auth Register',
            '/Auth/logout?lala=lala'                             => 'Auth Log Out',
            '/Auth/ResetPasswordRequest?lala=lala'               => 'Auth ResetPasswordRequest',
            '/Auth/ResetPasswordWithCode?lala=lala'              => 'Auth ResetPasswordWithCode',
            '/root/index?lalala=333'                             => 'Root with GET',
            '/egCookie/Test001'                                  => 'COOKIE',
            '/FormPatternsInvestigation/index/'                  => 'Form Patterns Investigation',
            '/AdminDbManager/EditRow/user/1'                     => 'Edit a DB line',
            '/alinaRestAccept/index?cmd=Model&m=user&mId=1'      => 'Rest call',
            '/NotExistingPage'                                   => 'Test 404',
            '/tools/SerializedDataEditor'                        => 'Serialized Data Editor',
            '/CtrlDataTransformations/json'                      => 'JSON search-replace',
            '/AdminDbManager/DbTablesColumnsInfo'                => 'MySQL Manager',
            '/SendRestApiQueries/BaseCurlCalls'                  => 'HTTP calls',
            '/AdminTests/Errors'                                 => 'Tst Errors',
            '/AdminTests/Serialization'                          => 'Tst Serialization',
            '/AdminTests/JsonEncode'                             => 'Tst Json Encode',
        ];
        AlinaEcho((new html())->page($vd));
    }

    public function actionIndex3()
    {
        $vd = \alina\Utils\FS::dirToClassActionIndex(ALINA_PATH_TO_FRAMEWORK . '/mvc/Controller');
        AlinaEcho((new html())->page($vd));
    }

    public function action404()
    {
        AlinaResponseSuccess(0);
        http_response_code(404);
        AlinaEcho((new html())->page());
    }

    public function actionException($vd = null)
    {
        AlinaResponseSuccess(0);
        http_response_code(500);
        AlinaEcho((new html())->page($vd, html::$htmLayoutErrorCatcher));
    }

    public function actionAccessDenied($code = 403)
    {
        AlinaResponseSuccess(0);
        http_response_code($code);
        AlinaEcho((new html())->page(null, html::$htmLayoutErrorCatcher));
    }
}
