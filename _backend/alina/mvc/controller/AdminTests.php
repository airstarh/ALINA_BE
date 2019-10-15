<?php

namespace alina\mvc\controller;

use alina\exceptionCatcher;
use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\view\json as jsonView;

class AdminTests
{
    ##############################################
    /**
     * @route /AdminTests/Errors
     */
    public function actionErrors(...$args)
    {
        try {
            //$x = 10 / 0;
            throw new \ErrorException(11111111111111);
        } catch (\Exception $e) {
            //throw $e;
            exceptionCatcher::obj()->exception($e, FALSE);
            echo (new \alina\mvc\view\html)->page('1234');
        }

        return $this;
    }
    ##############################################
    /**
     * @route /AdminTests/Serialization
     */
    public function actionSerialization()
    {
        $d = require_once ALINA_PATH_TO_FRAMEWORK.'/_MISC_CONTENT/complicated_nixed_object.php';
        echo '<pre>';
        print_r(serialize($d));
        echo '</pre>';
        return $this;
    }

    ##############################################
    /**
     * @route /AdminTests/JsonEncode
     */

    public function actionJsonEncode()
    {
        $d = require_once ALINA_PATH_TO_FRAMEWORK.'/_MISC_CONTENT/complicated_nixed_object.php';
        echo '<pre>';
        print_r(json_encode($d));
        echo '</pre>';
        return $this;
    }

    ##############################################
    /**
     * @route /AdminTests/ListTableColumns?table=user
     */

    public function actionListTableColumns()
    {
        $vd = (new _BaseAlinaModel(['table' => $_GET['table']]))->fields();
        (new jsonView())->standardRestApiResponse($vd);
        return $this;
    }


}
