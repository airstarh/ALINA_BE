<?php

namespace alina\mvc\controller;

use alina\mvc\view\html as htmlAlias;
use alina\utils\HttpRequest;

class SendRestApiQueries
{
    /**
     * @route /SendRestApiQueries/index
     */
    public function actionIndex()
    {
        ############################################
        #region Defaults
        $reqUri     = 'http://redindex:4567/index/search?text=abc';
        $reqUri     = 'http://alinazero:8080/dev/info/?lala=test&great=Привет!!!';
        $reqHeaders = (object)[];
        $reqGet     = (object)[];
        $reqPost    = (object)[];
        #endregion Defaults
        ############################################
        #region Process Page Query
        $p = resolvePostDataAsObject();
        if (property_exists($p, 'reqUri')) {
            $reqUri = $p->reqUri;
        }
        if (property_exists($p, 'reqHeaders')) {
            $reqHeaders = json_decode($p->reqHeaders, 1);
        }
        if (property_exists($p, 'reqGet')) {
            $reqGet = json_decode($p->reqGet, 1);
        }
        if (property_exists($p, 'reqPost')) {
            $reqPost = json_decode($p->reqPost, 1);
        }
        #endregion Process Page Query
        ############################################
        #region MAIN
        $q = new HttpRequest();
        $q->setUri($reqUri);
        $q->addGet($reqGet);
        $q->addPost($reqPost);
        $q->addHeaders($reqHeaders);
        #region Corrections after URI is defined
        $reqUri = $q->uri;
        $reqGet = $q->get;
        #endregion Corrections after URI is defined
        $q->exe();
        #endregion MAIN
        ############################################
        #regionn View
        $vd = (object)[
            'reqUri'     => $reqUri,
            'reqHeaders' => $reqHeaders,
            'reqGet'     => $reqGet,
            'reqPost'    => $reqPost,
            'q'          => $q,
        ];
        echo (new htmlAlias)->page($vd);
        #endregionn View
        ############################################

        return $this;
    }
}
