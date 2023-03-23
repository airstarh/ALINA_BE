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
        $reqUri     = 'https://alinazero:7002/tale/feed';
        $reqUri     = 'https://saysimsim.ru/tale/feed';
        $reqUri     = 'https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css';
        $reqUri     = 'https://local.host:7002/php-reply-what-received.php';
        $reqGet     = (object)[];
        $reqPost    = (object)[];
        $reqHeaders = (object)[];
        $reqCookie  = (object)[];
        $reqPostRaw = 0;
        $q          = new HttpRequest();
        $methods    = $q->take('methods');
        $reqMethod  = $q->take('reqMethod');
        #endregion Defaults
        ############################################
        if (Request::isPost($p)) {
            ############################################
            #region Process POST Query
            if (property_exists($p, 'reqUri')) {
                $reqUri = $p->reqUri ?: '';
            }
            if (property_exists($p, 'reqMethod')) {
                $reqMethod = $p->reqMethod;
            }
            if (property_exists($p, 'reqGet')) {
                $reqGet = json_decode($p->reqGet, 1) ?: (object)[];
            }
            if (property_exists($p, 'reqPost')) {
                if (property_exists($p, 'reqPostRaw')) {
                    $reqPostRaw = $p->reqPostRaw;
                    $reqPost    = $p->reqPost;
                }
                else {
                    $reqPost = json_decode($p->reqPost, 1) ?: (object)[];
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
            $q->setReqUri($reqUri);
            $q->setReqMethod($reqMethod);
            $q->addGet((array)$reqGet);
            $q->setFields((array)$reqPost);
            $q->addHeaders((array)$reqHeaders);
            $q->addCookie((array)$reqCookie);
            $q->setFlagFieldsRaw($reqPostRaw);
            $q->exe();
            #endregion MAIN
            ############################################
            #region Corrections after Request
            $reqUri     = $q->take('reqUri');
            $reqMethod  = $q->take('reqMethod');
            $reqGet     = $q->take('reqGet');
            $reqPost    = $q->take('reqFields');
            $reqHeaders = $q->take('reqHeaders');
            $reqCookie  = $q->take('reqCookie');
            $reqPostRaw = $q->take('flagFieldsRaw');
            #endregion Corrections after Request
            ############################################
        }
        ############################################
        #regionn View
        $vd = (object)[
            'form_id'    => __FUNCTION__,
            'reqUri'     => $reqUri,
            'reqGet'     => $reqGet,
            'reqPost'    => $reqPost,
            'reqHeaders' => $reqHeaders,
            'reqCookie'  => $reqCookie,
            'reqPostRaw' => $reqPostRaw,
            'methods'    => $methods,
            'reqMethod'  => $reqMethod,
            'q'          => $q,
        ];
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        #endregionn View
        ############################################
        return $this;
    }
}
