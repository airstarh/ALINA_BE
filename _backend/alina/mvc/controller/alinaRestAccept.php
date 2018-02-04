<?php

namespace alina\mvc\controller;

use alina\mvc\model\modelNamesResolver;

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
                        (new \alina\mvc\view\json())->standardRestApiResponse($data);
                    }
                }
                break;
        }
    }

    public function actionIndex2()
    {
        $data = resolvePostDataAsObject();
        $this->standardRestApiResponse($data);
    }

    public function actionIndex1()
    {

        $m   = new \alina\mvc\model\user();
        $res = $m->getAll();

        $this->standardRestApiResponse($res);
    }

    public function actionForm()
    {
        $data = '';
        echo (new \alina\mvc\view\html)->page($data);
    }

}