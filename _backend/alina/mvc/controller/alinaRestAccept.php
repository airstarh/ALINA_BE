<?php

namespace alina\mvc\controller;

use alina\GlobalRequestStorage;
use alina\message;
use alina\mvc\model\hero;
use alina\mvc\model\modelNamesResolver;
use alina\mvc\view\json as jsonView;

class alinaRestAccept
{
    public function actionIndex()
    {
        message::set('Hello, World!!!');
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        switch ($method) {
            //INSERT
            case 'POST':
                $post    = resolvePostDataAsObject();
                $command = $_GET['cmd'];
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
                $post = resolvePostDataAsObject();
                //$post->description = time();
                $command = $_GET['cmd'];
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
                if (isset($_GET['cmd']) && !empty($_GET['cmd'])) {
                    $command = $_GET['cmd'];
                    if ($command === 'model') {
                        $modelName = $_GET['m'];
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $data      = $m->getAllWithReferences();
                        GlobalRequestStorage::set('modelFields', $m->fields());
                        GlobalRequestStorage::set('pageCurrentNumber', $m->pageCurrentNumber);
                        GlobalRequestStorage::set('pageSize', $m->pageSize);
                        GlobalRequestStorage::set('rowsTotal', $m->rowsTotal);
                        (new jsonView())->standardRestApiResponse($data);
                    }

                    if ($command === 'modelOne') {
                        $modelName = $_GET['m'];
                        $mId       = $_GET['mId'];
                        $m         = modelNamesResolver::getModelObject($modelName);
                        $cond      = [$m->pkName => $mId];
                        $data      = $m->getAllWithReferences($cond);
                        $resp = null;
                        if (!empty($data)) {
                            $resp = $data[0];
                        }
                        (new jsonView())->standardRestApiResponse($resp);
                    }
                }
                break;
        }
    }

    public function actionNgHeroes(...$routeData)
    {
        (new jsonView())->systemData();
        {
            $method = strtoupper($_SERVER['REQUEST_METHOD']);
            $m      = new hero();

            switch ($method) {
                case 'POST':
                    $post = resolvePostDataAsObject();
                    $data = $m->insert($post);
                    (new jsonView())->simpleRestApiResponse($data);
                    break;
                case 'PUT':
                    $post = resolvePostDataAsObject();
                    $data = $m->updateById($post);
                    (new jsonView())->simpleRestApiResponse($data);
                    break;
                case 'DELETE':
                    if (isset($routeData) && !empty($routeData)) {
                        $id = array_shift($routeData);
                        $m->deleteById($id);
                    }
                    (new jsonView())->simpleRestApiResponse(NULL);
                    break;
                case 'GET':
                default:
                    if (isset($routeData) && !empty($routeData)) {
                        $id   = array_shift($routeData);
                        $data = $m->getAllWithReferences(["{$m->alias}.{$m->pkName}" => $id])[0];
                        (new jsonView())->simpleRestApiResponse($data);
                    }
                    else {
                        $data = $m->getAllWithReferences();
                        (new jsonView())->simpleRestApiResponse($data);
                    }

                    break;
            }
        }
    }

    public function actionForm()
    {
        $data = '';
        echo (new \alina\mvc\view\html)->page($data);
    }
}