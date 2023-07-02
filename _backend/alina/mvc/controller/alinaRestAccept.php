<?php
/**
 * URL's to test:
 * short:
 * /alinaRestAccept?cmd=model&m=user
 * current full:
 * /alinaRestAccept?cmd=collection&m=user&ps=2
 * /alinaRestAccept?cmd=collection&m=user&ps=2
 */

namespace alina\mvc\controller;

use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\model\modelNamesResolver;
use alina\mvc\view\html as htmlAlias;
use alina\mvc\view\json as jsonView;
use alina\utils\Sys;

class alinaRestAccept
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @throws \ErrorException
     * @throws \Exception
     * @throws \alina\AppExceptionValidation
     * @link /alinaRestAccept?cmd=model&m=user&mId=1
     * @link /alinaRestAccept
     */
    public function actionIndex()
    {
        Sys::setCrossDomainHeaders();
        MessageAdmin::setSuccess('Hello, Admin!!!');
        Message::setSuccess('Hello, User!!!');
        \alina\AppCookie::setPath('serverCookie', 'Hello from server Alina');
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
                    echo (new jsonView())->standardRestApiResponse($data);
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
                    echo (new jsonView())->standardRestApiResponse($data[0]);
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
                        GlobalRequestStorage::set('rowsTotal', $m->state_ROWS_TOTAL);
                        echo (new jsonView())->standardRestApiResponse($data);
                    }
                    if ($command === 'model') {
                        $modelName = $_GET['m'];
                        $mId       = $_GET['mId'];
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $cond      = ["{$m->alias}.{$m->pkName}" => $mId];
                        $data      = $m->getAllWithReferences($cond);
                        $resp      = NULL;
                        if (!empty($data)) {
                            foreach ($data as $pk => $d) {
                                $resp = $d;
                                break;
                            }
                        }
                        //$resp = $data;
                        echo (new jsonView())->standardRestApiResponse($resp);
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
        echo (new jsonView())->standardRestApiResponse($_GET);
    }

    public function actionTestCors()
    {
        Sys::setCrossDomainHeaders();
        \alina\AppCookie::setPath('AlinaCookie', 'Hello, cookie');
        //$vd = Request::obj()->all();
        $vd = 'Привет';
        ############################################
        //echo (new htmlAlias)->page($vd);
        echo (new jsonView())->standardRestApiResponse($vd);
    }
}
