<?php
/**
 * URL's to test:
 * short:
 * /alinaRestAccept?cmd=model&m=user
 * current full:
 * http://alinazero/alinaRestAccept?cmd=collection&m=user&ps=2
 * http://alinazero:8080/alinaRestAccept?cmd=collection&m=user&ps=2
 */

namespace alina\mvc\controller;

use alina\GlobalRequestStorage;
use alina\message;
use alina\mvc\model\hero;
use alina\mvc\model\modelNamesResolver;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\Request;
use alina\utils\Sys;

class alinaRestAccept
{
    /**
     * @link /alinaRestAccept
     * @link /alinaRestAccept?cmd=model&m=user&mId=1
     * @throws \ErrorException
     * @throws \Exception
     * @throws \alina\exceptionValidation
     */
    public function actionIndex()
    {

        Sys::setCrossDomainHeaders();
        message::set('Hello, World!!!');
        \alina\cookie::setPath('serverCookie', 'Hello from server Alina');

        $method  = strtoupper($_SERVER['REQUEST_METHOD']);
        $command = $_GET['cmd'];
        switch ($method) {
            //INSERT
            case 'POST':
                $post = Sys::resolvePostDataAsObject();
                if ($command === 'model') {
                    $modelName = $_GET['m'];
                    $m         = modelNamesResolver::getModelObject($modelName);
                    $m->insert($post);
                    $data = $m->getAllWithReferences(["{$m->alias}.{$m->pkName}" => $m->{$m->pkName}])[0];
                    (new jsonView())->standardRestApiResponse($data);
                }
                break;
            //UPDATE
            case 'PUT':
                $post = Sys::resolvePostDataAsObject();
                if ($command === 'model') {
                    $modelName = $_GET['m'];
                    $m         = modelNamesResolver::getModelObject($modelName);
                    $id        = $post->{$m->pkName};
                    $m->updateById($post);
                    $data = $m->getAllWithReferences(["{$m->alias}.{$m->pkName}" => $id]);
                    (new jsonView())->standardRestApiResponse($data[0]);
                }
                break;
            case 'OPTIONS':
                (new jsonView())->simpleRestApiResponse('o.k.');
                break;
            case 'GET':
            default:
                /**
                 *  /?cmd=model&m=user&[search_parameters]
                 */
                if ($command && !empty($command)) {
                    if ($command === 'collection') {
                        $modelName = $_GET['m'];
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $data      = $m->getAllWithReferences();
                        GlobalRequestStorage::set('modelMetaInfo', $m->getFieldsMetaInfo());
                        GlobalRequestStorage::set('pageCurrentNumber', $m->pageCurrentNumber);
                        GlobalRequestStorage::set('pageSize', $m->pageSize);
                        GlobalRequestStorage::set('rowsTotal', $m->rowsTotal);
                        (new jsonView())->standardRestApiResponse($data);
                    }

                    if ($command === 'model') {
                        $modelName = $_GET['m'];
                        $mId       = $_GET['mId'];
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $cond      = ["{$m->alias}.{$m->pkName}" => $mId];
                        $data      = $m->getAllWithReferences($cond);
                        $resp      = NULL;
                        if (!empty($data)) {
                            $resp = $data[0];
                        }
                        (new jsonView())->standardRestApiResponse($resp);
                    }
                }
                break;
        }
    }

    public function actionForm()
    {
        $data = '';
        echo (new \alina\mvc\view\html)->page($data);
    }

    /**
     * @link /alinaRestAccept/TestGet
     */
    public function actionTestGet()
    {
        Sys::setCrossDomainHeaders();
        error_log('>>> - - - - - - - - - - - - - - - - - - - - - - - - - ',0);
        error_log(__FUNCTION__,0);
        error_log("URL: {$_SERVER['REQUEST_URI']}",0);
        error_log(json_encode(func_get_args()),0);
        error_log(json_encode($_GET),0);
        error_log(json_encode(getallheaders()),0);
        error_log('<<< - - - - - - - - - - - - - - - - - - - - - - - - - ',0);
        (new jsonView())->standardRestApiResponse($_GET);
    }

    public function actionTestCors()
    {
        Sys::setCrossDomainHeaders();
        \alina\cookie::setPath('AlinaCookie', 'Hello, cookie');
        $vd = Request::obj()->all();
        ############################################
        //echo (new htmlAlias)->page($vd);
        (new jsonView())->standardRestApiResponse($vd);
    }

}
