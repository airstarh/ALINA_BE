<?php

namespace alina\mvc\controller;

use alina\App;
use alina\Message;
use alina\mvc\model\DataPlayer;
use alina\mvc\model\modelNamesResolver;
use alina\mvc\model\user;
use alina\mvc\view\html as htmlAlias;
use alina\utils\Data;
use alina\utils\db\mysql\DbManager;
use alina\utils\Request;
use alina\utils\Sys;
use PDO;

class AdminDbManager
{
    public function __construct()
    {
        AlinaRejectIfNotAdmin();
    }

    /**
     * @route /AdminDbManager/DbTablesColumnsInfo
     * @file _backend/alina/mvc/template/AdminDbManager/actionDbTablesColumnsInfo.php
     */
    public function actionDbTablesColumnsInfo()
    {
        ##########################################################################################
        $strNoPkInTable = 'ATTENTION_NO_PK_NAME';
        //ToDo: Security! Hardcoded.
        $vd = (object)[
            'alina_form_db_host' => AlinaCfg('db/host'),
            'alina_form_db_user' => AlinaCfg('db/username'),
            'alina_form_db_pass' => AlinaCfg('db/password'),
            'alina_form_db_db'   => AlinaCfg('db/database'),
            'alina_form_db_port' => AlinaCfg('db/port'),
            'form_id'            => __FUNCTION__,
            'strSqlSELECT'       => '',
            'strSqlINSERT'       => '',
            'strSqlUPDATE'       => '',
            'strSqlDELETE'       => '',
            'strSqlPDObind'      => '',
            'arrTables'          => [],
            'tableName'          => '',
            'arrColumns'         => [],
            'arrColumnsCount'    => 0,
            'tColsInfo'          => [],
            'pkName'             => $strNoPkInTable,
            'rowsInTable'        => 0,
            'colsAsJson'         => '',
            'colsAsPHPArr'       => '',
        ];
        if (Request::isPost($p)) {
            $p               = \alina\utils\Data::deleteEmptyProps($p);
            $vd              = \alina\utils\Data::mergeObjects($vd, $p);
            $r               = [];
            $exe             = [];
            $q               = new DbManager();
            $arrTablesPk     = [];
            $arrTables       = [];
            $arrColumns      = [];
            $arrColumnsCount = 0;
            ##########################################################################################
            $q->setCredentials($vd);
            $qResp = $q->qsGetColumnInformation();
            foreach ($qResp as $x) {
                $exe[$x->TABLE_SCHEMA][$x->TABLE_NAME][$x->COLUMN_NAME] = $x;
                if (!isset($arrTablesPk[$x->TABLE_NAME])) {
                    $arrTablesPk[$x->TABLE_NAME] = [];
                }
                if (strtoupper($x->COLUMN_KEY) === 'PRI') {
                    $arrTablesPk[$x->TABLE_NAME]['pkName'] = $x->COLUMN_NAME;
                }
            }
            $arrTables     = array_keys($arrTablesPk);
            $vd->arrTables = $arrTables;
            //$r['exe'] = $exe;
            ##########################################################################################
            if (@$vd->tableName && in_array($vd->tableName, $vd->arrTables)) {
                $db                  = $vd->alina_form_db_db;
                $tableName           = $vd->tableName;
                $tColsInfo           = $exe[$db][$tableName];
                $arrColumns          = array_keys($exe[$db][$tableName]);
                $arrColumnsCount     = count($arrColumns);
                $pkName              = @$arrTablesPk[$tableName]['pkName'] ?: $strNoPkInTable;
                $arrColumnsWithoutPk = array_diff($arrColumns, [$pkName]);
                #
                $vd->tColsInfo       = $tColsInfo;
                $vd->arrColumns      = $arrColumns;
                $vd->arrColumnsCount = $arrColumnsCount;
                $vd->pkName          = $pkName;
                #
                $dataTpl = (object)[
                    'tableName'           => $tableName,
                    'arrColumns'          => $arrColumns,
                    'pkName'              => $pkName,
                    'arrColumnsWithoutPk' => $arrColumnsWithoutPk,
                ];
                ###############
                # SELECT
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/SELECT.php';
                $vd->strSqlSELECT = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                # INSERT
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/INSERT.php';
                $vd->strSqlINSERT = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                # UPDATE
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/UPDATE.php';
                $vd->strSqlUPDATE = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                # DELETE
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/DELETE.php';
                $vd->strSqlDELETE = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                # PDO bind parameters
                $tpl               = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/PDObind.php';
                $vd->strSqlPDObind = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                # JSON view
                $tpl            = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/colsAsJson.php';
                $vd->colsAsJson = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                # Array 'field' => [],
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/colsAsPHPArr.php';
                $vd->colsAsPHPArr = \alina\utils\Sys::template($tpl, $dataTpl);
                ###############
                ###############
                # Statistics. Count Rows.
                $sql             = "SELECT COUNT(*) as rowsInTable FROM $tableName";
                $rowsInTable     = $q->qExecFetchAll($sql)[0]->rowsInTable;
                $vd->rowsInTable = $rowsInTable;
            }
        }
        ##########################################################################################
        //$vd->result = $r;
        //$vd->arrTables = $arrTables;
        ##########################################################################################
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayout.php');
    }

    public function actionEditRow($modelName, $id)
    {
        try {
            $vd          = (object)[];
            $m           = modelNamesResolver::getModelObject($modelName);
            $vd->model   = $m;
            $vd->sources = $m->getReferencesSources();
            $m->getAllWithReferences();
            ##################################################
            $p = Data::deleteEmptyProps(Sys::resolvePostDataAsObject());
            if (!empty((array)$p)) {
                $m->upsert($p);
                $m->getAllWithReferences();
            }
            ##################################################
        } catch (\Exception $e) {
            Message::setDanger($e->getMessage(), []);
            Message::setDanger($e->getFile(), []);
            Message::setDanger($e->getLine(), []);
        }
        echo (new htmlAlias)->page($vd);
    }
}
