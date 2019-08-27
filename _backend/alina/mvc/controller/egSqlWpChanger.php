<?php

namespace alina\mvc\controller;

use alina\utils\db\mysql\DbManager;
use PDO;

class egSqlWpChanger
{
    /**
     * @route http://alinazero:8080/egSqlWpChanger/index
     */
    public function actionIndex()
    {
        $q = new DbManager();
        $q->change('flagForceTransaction', TRUE);

        $sql = "SELECT option_name FROM wp_options";
        $d   = $q->qExecFetchAll($sql);
        $d   = $q->qExecFetchColumn(0, $sql);
        $d   = $q->qExecPluck($sql);

        //>>>
        // $o = (object)[
        //   'tableName' => 'wp_options',
        //   'db' => 'stage001',
        // ];
        // $fileFullPath = ALINA_PATH_TO_FRAMEWORK.'/utils/db/mysql/queryTemplates/AllTableFields.sql';
        // $sql = template($fileFullPath, $o);
        // $d   = $q->qExecFetchAll($sql);
        //$d   = $q->qExecFetchColumn(0,$sql);
        //<<<

        ////////////
        ////////////
        ////////////
        echo '<pre>';
        print_r($d);
        echo '</pre>';
    }
}
