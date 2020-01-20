<?php

namespace alina\mvc\controller;

use alina\app;
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
            'alina_form_db_host' => app::getConfig('db/host'),
            'alina_form_db_user' => app::getConfig('db/username'),
            'alina_form_db_pass' => app::getConfig('db/password'),
            'alina_form_db_db'   => app::getConfig('db/database'),
            'alina_form_db_port' => app::getConfig('db/port'),
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
        if (Request::obj()->METHOD === 'POST') {
            $p               = \alina\utils\Data::deleteEmptyProps(Request::obj()->POST);
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

    /**
     * @route http://alinazero:8080/egSqlWpChanger/index
     */
    public function actionIndexWP()
    {
        $tables = [

        ];
        #region Strings
        $s1    = '%//sixtyandme.com%';
        $s2    = '%//www.sixtyandme.com%';
        $s1old = '//sixtyandme.com';
        $s1New = '//stage.sixtyandme.com';
        $s2old = '//www.sixtyandme.com';
        $s2New = '//www.stage.sixtyandme.com';
        #endregion Strings

        $q = new DbManager();
        $q->change('flagForceTransaction', FALSE);
        $resFoundStringsCount = [];
        $repFoundStrPieces    = [];
        $tRowsIteratedCount   = 0;
        $tSqlCount            = 0;
        $iCount               = 0;
        $tChangesCount        = 0;
        $repSPECIAL           = [];
        foreach ($tables as $t) {
            $fields = $q->qsGetTableFields($t);
            foreach ($fields as $f) {
                $sql             = "SELECT $f FROM $t WHERE $f LIKE :s1 OR $f LIKE :s2";
                $par             = [
                    ':s1' => $s1,
                    ':s2' => $s2,
                ];
                $arrFoundStrings = $q->qExecPluck($sql, $par);
                if (!empty($arrFoundStrings)) {
                    $resFoundStringsCount[$t][$f] = count($arrFoundStrings);
                    foreach ($arrFoundStrings as $strInit) {
                        $strReplace = $strInit;
                        if (FALSE != \alina\utils\Data::megaUnserialize($strInit)) {
                            $isSerialized  = 'YES';
                            $data          = Data::serializedArraySearchReplace($strReplace, $s1old, $s1New);
                            $strReplace    = $data->strResControl;
                            $tChangesCount += $data->tCount;
                            $data          = Data::serializedArraySearchReplace($strReplace, $s2old, $s2New);
                            $strReplace    = $data->strResControl;
                            $tChangesCount += $data->tCount;
                        } else {
                            $isSerialized  = 'NO';
                            $strReplace    = str_replace($s1old, $s1New, $strReplace, $iCount);
                            $tChangesCount += $iCount;
                            $strReplace    = str_replace($s2old, $s2New, $strReplace, $iCount);
                            $tChangesCount += $iCount;
                        }
                        $sql = "UPDATE {$t} SET {$f} = :strReplace WHERE {$f} = :strInit";
                        $par = [
                            ':strReplace' => $strReplace,
                            ':strInit'    => $strInit,
                        ];
                        #region ATTENTION!!! The Most Dangerous String !!!
                        // ToDo: ATTENTION!!! The Most Dangerous String !!!
                        //$tSqlCount    += $q->qExecGetAffectedRows($sql, $par);
                        #endregion ATTENTION!!! The Most Dangerous String !!!
                        $reportstring                = $strReplace;
                        $reportstring                = htmlspecialchars($reportstring, ENT_QUOTES | ENT_SUBSTITUTE);
                        $reportstring                = \alina\utils\Str::removeEnters($reportstring);
                        $reportstring                = mb_substr($reportstring, 0, 50);
                        $repFoundStrPieces[$t][$f][] = $isSerialized . '|||' . $reportstring;
                        $tRowsIteratedCount++;
                    }
                }
            }
        }

        $repott = (object)[
            '$tRowsIteratedCount' => $tRowsIteratedCount,
            '$tSqlCount'          => $tSqlCount,
            '$tChangesCount'      => $tChangesCount,
            '$repFoundStrPieces'  => $repFoundStrPieces,
            '$repSPECIAL'         => $repSPECIAL,
        ];
        //fDebug([$repFoundStrPieces, $resFoundStringsCount], FALSE);
        echo (new htmlAlias)->page($repott);
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
            Message::set($e->getMessage(), [], 'alert alert-danger');
            Message::set($e->getFile(), [], 'alert alert-danger');
            Message::set($e->getLine(), [], 'alert alert-danger');
        }

        echo (new htmlAlias)->page($vd);
    }
}
