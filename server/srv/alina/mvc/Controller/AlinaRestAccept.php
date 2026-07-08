<?php

/**
 * URL's to test:
 * short:
 * /alinaRestAccept?cmd=Model&m=user
 * current full:
 * /alinaRestAccept?cmd=collection&m=user&ps=2
 * /alinaRestAccept?cmd=collection&m=user&ps=2
 */

namespace alina\mvc\Controller;

use alina\AppCookie;
use alina\GlobalRequestStorage;
use alina\Message;
use alina\MessageAdmin;
use alina\mvc\Model\modelNamesResolver;
use alina\mvc\View\json as jsonView;
use alina\Utils\Request;
use ErrorException;
use Exception;

class AlinaRestAccept
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @throws ErrorException
     * @throws Exception
     * @throws \alina\AppExceptionValidation
     * @link /alinaRestAccept?cmd=Model&m=user&mId=1
     * @link /alinaRestAccept
     */
    public function actionIndex()
    {
        MessageAdmin::setSuccess('Hello, Admin!!!');
        Message::setSuccess('Hello, User!!!');
        AppCookie::setPath('serverCookie', 'Hello from server Alina');
        $method  = Request::obj()->METHOD;
        $command = Request::obj()->GET->cmd;
        switch ($method) {
            //INSERT
            case 'POST':
                $post = Request::obj()->POST;

                if ($command === 'Model') {
                    $modelName = Request::obj()->GET->m;
                    $m         = modelNamesResolver::getModelObject($modelName);
                    $m->insert($post);
                    $data = $m->getAllWithReferences(["{$m->alias}.{$m->pkName}" => $m->{$m->pkName}])[0];
                    AlinaEcho((new jsonView())->standardRestApiResponse($data));
                }

                break;
                //UPDATE
            case 'PUT':
                $post = Request::obj()->POST;

                if ($command === 'Model') {
                    $modelName = Request::obj()->GET->m;
                    $m         = modelNamesResolver::getModelObject($modelName);
                    $id        = $post->{$m->pkName};
                    $m->updateById($post);
                    $data = $m->getAllWithReferences(["{$m->alias}.{$m->pkName}" => $id]);
                    AlinaEcho((new jsonView())->standardRestApiResponse($data[0]));
                }

                break;
            case 'OPTIONS':
                (new jsonView())->simpleRestApiResponse('o.k.');

                break;
            case 'GET':
            default:
                /**
                 *  /?cmd=Model&m=user&[search_parameters]
                 */
                if ($command && ! empty($command)) {
                    if ($command === 'collection') {
                        $modelName = Request::obj()->GET->m;
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $data      = $m->getAllWithReferences();
                        GlobalRequestStorage::set('modelMetaInfo', $m->getFieldsMetaInfo());
                        GlobalRequestStorage::set('pageCurrentNumber', $m->pageCurrentNumber);
                        GlobalRequestStorage::set('pageSize', $m->pageSize);
                        GlobalRequestStorage::set('rowsTotal', $m->state_ROWS_TOTAL);
                        AlinaEcho((new jsonView())->standardRestApiResponse($data));
                    }

                    if ($command === 'Model') {
                        $modelName = Request::obj()->GET->m;
                        $mId       = Request::obj()->GET->mid;
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $cond      = ["{$m->alias}.{$m->pkName}" => $mId];
                        $data      = $m->getAllWithReferences($cond);
                        $resp      = null;

                        if (! empty($data)) {
                            foreach ($data as $pk => $d) {
                                $resp = $d;

                                break;
                            }
                        }
                        //$resp = $data;
                        AlinaEcho((new jsonView())->standardRestApiResponse($resp));
                    }
                }

                break;
        }
    }

    public function actionForm()
    {
        $data = '';
        AlinaEcho((new \alina\mvc\View\html())->page($data));
    }

    /**
     * @link /alinaRestAccept/TestGet
     */
    public function actionTestGet()
    {
        AlinaEcho((new jsonView())->standardRestApiResponse($_GET));
    }

    public function actionTestCors()
    {
        AppCookie::setPath('AlinaCookie', 'Hello, cookie');
        //$vd = Request::obj()->all();
        $vd = 'Привет';
        ############################################
        AlinaEcho((new jsonView())->standardRestApiResponse($vd));
    }
}
