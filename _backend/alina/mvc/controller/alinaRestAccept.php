<?php

namespace alina\mvc\controller;

use alina\mvc\model\hero;
use alina\mvc\model\modelNamesResolver;
use alina\mvc\view\json as jsonView;

class alinaRestAccept
{
    public function actionIndex()
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        switch ($method) {
            case 'POST':
            case 'PUT':
                $post = resolvePostDataAsObject();

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
                        (new jsonView())->standardRestApiResponse($data);
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
                        $data = $m->getAllWithReferences([$m->pkName => $id])[0];
                        (new jsonView())->simpleRestApiResponse($data);
                    } else {
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