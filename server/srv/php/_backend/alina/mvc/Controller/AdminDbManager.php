<?php

namespace alina\mvc\Controller;

use alina\mvc\Model\modelNamesResolver;
use alina\mvc\View\html as htmlAlias;
use alina\traits\RequestProcessor;
use alina\Utils\Data;
use alina\Utils\db\mysql\DbManager;
use alina\Utils\Request;
use alina\Utils\Sys;

class AdminDbManager
{
    use RequestProcessor;

    public function __construct()
    {
        //AlinaRejectIfNotAdmin();
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
            $p               = \alina\Utils\Data::deleteEmptyProps($p);
            $vd              = \alina\Utils\Data::mergeObjects($vd, $p);
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
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/SELECT.php';
                $vd->strSqlSELECT = \alina\Utils\Sys::template($tpl, $dataTpl);
                ###############
                # INSERT
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/INSERT.php';
                $vd->strSqlINSERT = \alina\Utils\Sys::template($tpl, $dataTpl);
                ###############
                # UPDATE
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/UPDATE.php';
                $vd->strSqlUPDATE = \alina\Utils\Sys::template($tpl, $dataTpl);
                ###############
                # DELETE
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/DELETE.php';
                $vd->strSqlDELETE = \alina\Utils\Sys::template($tpl, $dataTpl);
                ###############
                # PDO bind parameters
                $tpl               = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/PDObind.php';
                $vd->strSqlPDObind = \alina\Utils\Sys::template($tpl, $dataTpl);
                ###############
                # JSON View
                $tpl            = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/colsAsJson.php';
                $vd->colsAsJson = \alina\Utils\Sys::template($tpl, $dataTpl);
                ###############
                # Array 'field' => [],
                $tpl              = ALINA_PATH_TO_FRAMEWORK . '/Utils/db/mysql/queryTemplates/colsAsPHPArr.php';
                $vd->colsAsPHPArr = \alina\Utils\Sys::template($tpl, $dataTpl);
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
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
    }

    public function actionModels($model)
    {
        $vd    = (object)[];
        $model = modelNamesResolver::getModelObject($model);
        ########################################
        if (Request::isPost()) {
            $post = Data::deleteEmptyProps(Request::obj()->POST);
            switch ($post->action) {
                case 'update':
                    $model->updateById($post);
                    break;
                case 'delete':
                    $id = $model->{$model->pkName};
                    if (method_exists($model, 'bizDelete')) {
                        $model->bizDelete($id);
                    }
                    else {
                        $model->deleteById($id);
                    }
                    break;
            }
        }
        ########################################
        #region Models
        $model->state_APPLY_GET_PARAMS  = TRUE;
        $processResponse                = $this->processGetModelList($model);
        $collection                     = $processResponse['collection'];
        $pagination                     = $processResponse['pagination'];
        $vd->pagination                 = $pagination;
        $vd->pagination->path           = "/admin/models/{$model->table}";
        $vd->pagination->flagHrefAsPath = FALSE;
        $vd->model                      = $model;
        $vd->models                     = $collection->toArray();
        $vd->models                     = array_filter($vd->models, ['\alina\utils\Data', 'sanitizeOutputObj']);
        #endregion Models
        ########################################
        echo (new htmlAlias)->page($vd, htmlAlias::$htmLayoutWide);
    }

    public function actionEditRow($table, $id, $flagReturn = FALSE)
    {
        $vd          = (object)[];
        $m           = modelNamesResolver::getModelObject($table);
        $vd->model   = $m;
        $vd->sources = $m->getReferencesSources();
        ##################################################
        $p = Sys::resolvePostDataAsObject();
        if (!empty((array)$p)) {
            if (property_exists($p, 'owner_id')) {
                AlinaRejectIfNotAdminOrModeratorOrOwner($p->owner_id);
            }
            $m->upsert($p);
            $m->getOneWithReferences(["{$m->alias}.{$m->pkName}" => $id]);
        }
        ##################################################
        if ($flagReturn) {
            return $vd;
        }
        else {
            echo (new htmlAlias)->page($vd);
        }
    }

    public function actionUpdate($table, $id, $data)
    {
        $m = modelNamesResolver::getModelObject($table);
        $data = \alina\Utils\Data::toObject($data);
        $m->upsert($data);
        $m->getOneWithReferences(["{$m->alias}.{$m->pkName}" => $id]);

        return $m->attributes;
    }

    public function actionUpdateBulk($table)
    {
        $p = Request::obj()->POST;
        foreach ($p->list as $i => $m) {
            $p->list[$i] = $this->actionUpdate($table, $m->id, $m);
        }
        echo (new htmlAlias)->page($p);
    }
}
