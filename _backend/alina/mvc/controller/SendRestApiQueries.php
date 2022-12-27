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
    public $arrDefault = [];
    //public $arrDefault = [];

    /**
     * @route /SendRestApiQueries/BaseCurlCalls
     */
    public function actionBaseCurlCalls()
    {
        ############################################
        #region Defaults
        $reqUri     = 'http://sixtyandme.com/?test=123';
        $reqUri     = 'http://alinazero:8080/dev/info/?lala=test&great=Привет!!!';
        $reqUri     = 'http://redindex:4567/index/add';
        $reqUri     = 'http://redindex:4567/index/search?text=green';
        $reqUri     = 'http://alinazero:8080/AdminTests/TestMessages?lala[]=1&lala[]=2&lala[]=3&foo=bar';
        $reqHeaders = [];
        $reqGet     = $this->arrDefault;
        $reqPost    = $this->arrDefault;
        $reqPostRaw = 0;
        $q          = new HttpRequest();
        #endregion Defaults
        ############################################
        if (Request::isPost($p)) {
            ############################################
            #region Process POST Query
            if (property_exists($p, 'reqUri')) {
                $reqUri = $p->reqUri ?: '';
            }
            if (property_exists($p, 'reqHeaders')) {
                $reqHeaders = json_decode($p->reqHeaders, 1) ?: [];
            }
            if (property_exists($p, 'reqGet')) {
                $reqGet = json_decode($p->reqGet, 1) ?: [];
            }
            if (property_exists($p, 'reqPost')) {
                if (property_exists($p, 'reqPostRaw')) {
                    $reqPostRaw = $p->reqPostRaw;
                    $reqPost    = $p->reqPost;
                } else {
                    $reqPost = json_decode($p->reqPost, 1) ?: [];
                }
            }
            #endregion Process POST Query
            ############################################
            #region MAIN
            $q->setReqUri($reqUri);
            $q->addGet($reqGet);
            $q->setIsPostRaw($reqPostRaw);
            $q->addPost($reqPost);
            $q->addHeaders($reqHeaders);
            #region Corrections after URI is defined
            $reqUri = $q->reqUri;
            $reqGet = $q->reqGet;
            #endregion Corrections after URI is defined
            $q->exe();
            #endregion MAIN
        }
        ############################################
        #regionn View
        $vd = (object)[
            'form_id'    => __FUNCTION__,
            'reqUri'     => $reqUri,
            'reqHeaders' => $reqHeaders,
            'reqGet'     => $reqGet,
            'reqPost'    => $reqPost,
            'reqPostRaw' => $reqPostRaw,
            'q'          => $q,
        ];
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
        #endregionn View
        ############################################
        return $this;
    }
}
