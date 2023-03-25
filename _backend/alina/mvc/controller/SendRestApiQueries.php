<?php
// ToDo: Auto Execution
// ToDo: endless request to itself
namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;
use alina\utils\HttpRequest;
use alina\utils\Request;

class SendRestApiQueries
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }
    // public $arrDefault = [
    //     'opt1' => 10.5,
    //     'opt2' => 'Hello, world',
    //     'opt3' => ['Hello', 'world', 10.5],
    //     'opt4' => ['prop1' => 'val1', 'prop2' => 123.321],
    // ];
    //public $arrDefault = [];
    /**
     * @route /SendRestApiQueries/BaseCurlCalls
     */
    public function actionBaseCurlCalls()
    {
        ############################################
        #region Defaults
        $reqUrl                  = 'https://alinazero:7002/tale/feed';
        $reqUrl                  = 'https://saysimsim.ru/tale/feed';
        $reqUrl                  = 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css';
        $reqUrl                  = 'https://local.host:7002/php-reply-what-received.php?data_in_url=YO';
        $resUrl                  = ''; // What is finally sent in Request.
        $reqGet                  = (object)[
            'arr1' => [1, 2, 3],
            'arr2' => (object)['___prop____' => 'val',],
        ];
        $reqFields               = (object)[
            "Hello" => "World",
        ];
        $reqHeaders              = (object)[];
        $reqCookie               = (object)[];
        $flagFieldsRaw           = 1;
        $q                       = new HttpRequest();
        $methods                 = $q->take('methods');
        $reqMethod               = $q->take('reqMethod');
        $respBody                = $q->take('respBody');
        $curlInfo                = $q->take('curlInfo');
        $respHeadersStructurized = $q->take('respHeadersStructurized');
        #endregion Defaults
        ############################################
        if (Request::isPost($p)) {
            ############################################
            #region Process POST Query
            if (property_exists($p, 'reqUrl')) {
                $reqUrl = $p->reqUrl ?: '';
            }
            if (property_exists($p, 'reqMethod')) {
                $reqMethod = $p->reqMethod;
            }
            if (property_exists($p, 'reqGet')) {
                $reqGet = json_decode($p->reqGet, 1) ?: (object)[];
            }
            if (property_exists($p, 'flagFieldsRaw')) {
                $flagFieldsRaw = $p->flagFieldsRaw;
            }
            else {
                $flagFieldsRaw = 0;
            }
            if (property_exists($p, 'reqFields')) {
                if ($flagFieldsRaw) {
                    $reqFields = $p->reqFields;
                }
                else {
                    $reqFields = json_decode($p->reqFields, 1) ?: (object)[];
                }
            }
            if (property_exists($p, 'reqHeaders')) {
                $reqHeaders = json_decode($p->reqHeaders, 1) ?: (object)[];
            }
            if (property_exists($p, 'reqCookie')) {
                $reqCookie = json_decode($p->reqCookie, 1) ?: (object)[];
            }
            #endregion Process POST Query
            ############################################
            #region MAIN
            $q->setFlagFieldsRaw($flagFieldsRaw);
            $q->setReqUrl($reqUrl);
            $q->setReqMethod($reqMethod);
            $q->addGet((array)$reqGet);
            $q->setFields($reqFields);
            $q->addHeaders((array)$reqHeaders);
            $q->addCookie((array)$reqCookie);
            $q->exe();
            #endregion MAIN
            ############################################
            #region Corrections after Request
            $flagFieldsRaw = $q->take('flagFieldsRaw');
            $reqUrl        = $q->take('reqUrl');
            $reqMethod     = $q->take('reqMethod');
            $reqGet        = $q->take('reqGet');
            $reqFields     = $q->take('reqFields');
            $reqHeaders    = $q->take('reqHeaders');
            $reqCookie     = $q->take('reqCookie');
            #####
            $resUrl                  = $q->take('resUrl');
            $respBody                = $q->take('respBody');
            $curlInfo                = $q->take('curlInfo');
            $respHeadersStructurized = $q->take('respHeadersStructurized');
            #endregion Corrections after Request
            ############################################
        }
        ############################################
        #regionn View
        $vd = (object)[
            'form_id'                 => __FUNCTION__,
            'reqUrl'                  => $reqUrl,
            'reqGet'                  => $reqGet,
            'reqFields'               => $reqFields,
            'reqHeaders'              => $reqHeaders,
            'reqCookie'               => $reqCookie,
            'flagFieldsRaw'           => $flagFieldsRaw,
            'methods'                 => $methods,
            'reqMethod'               => $reqMethod,
            #####
            'resUrl'                  => $resUrl,
            'respBody'                => $respBody,
            'curlInfo'                => $curlInfo,
            'respHeadersStructurized' => $respHeadersStructurized,
        ];
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        #endregionn View
        ############################################
        return $this;
    }
}
