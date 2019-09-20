<?php

namespace alina\mvc\controller;

use alina\app;
use alina\mvc\model\DataPlayer;
use alina\mvc\view\html as htmlAlias;
use alina\utils\db\mysql\DbManager;
use PDO;

class AdminDbManager
{
    public function actionIndex()
    {
        ##########################################################################################
        $strNoPkInTable = 'ATTENTION_NO_PK_NAME';
        //ToDo: Security! Hardcoded.
        $vd              = (object)[
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
            'rowsInTable'             => 0,
        ];
        $p               = hlpEraseEmpty(resolvePostDataAsObject());
        $vd              = hlpMergeSimpleObjects($vd, $p);
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
            $sqlTplData = (object)[
                'tableName'           => $tableName,
                'arrColumns'          => $arrColumns,
                'pkName'              => $pkName,
                'arrColumnsWithoutPk' => $arrColumnsWithoutPk,
            ];
            ###############
            # SELECT
            $sqlTpl           = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/SELECT.php';
            $strSqlSELECT     = template($sqlTpl, $sqlTplData);
            $vd->strSqlSELECT = $strSqlSELECT;

            ###############
            # INSERT
            $sqlTpl           = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/INSERT.php';
            $strSqlINSERT     = template($sqlTpl, $sqlTplData);
            $vd->strSqlINSERT = $strSqlINSERT;

            ###############
            # UPDATE
            $sqlTpl           = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/UPDATE.php';
            $strSqlUPDATE     = template($sqlTpl, $sqlTplData);
            $vd->strSqlUPDATE = $strSqlUPDATE;
            ###############
            # DELETE
            $sqlTpl           = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/DELETE.php';
            $strSqlDELETE     = template($sqlTpl, $sqlTplData);
            $vd->strSqlDELETE = $strSqlDELETE;

            ###############
            # PDO bind parameters
            $sqlTpl            = ALINA_PATH_TO_FRAMEWORK . '/utils/db/mysql/queryTemplates/PDObind.php';
            $strSqlPDObind     = template($sqlTpl, $sqlTplData);
            $vd->strSqlPDObind = $strSqlPDObind;

            ###############
            # Statistics. Count Rows.
            $sql = "SELECT COUNT(*) as rowsInTable FROM $tableName";
            $rowsInTable = $q->qExecFetchAll($sql)[0]->rowsInTable;
            $vd->rowsInTable = $rowsInTable;
        }
        ##########################################################################################
        $vd->result = $r;
        //$vd->arrTables = $arrTables;
        ##########################################################################################
        $vd = hlpMergeSimpleObjects($vd, $p);
        echo (new htmlAlias)->page($vd, '_system/html/htmlLayout.php');
    }

    /**
     * @route http://alinazero:8080/egSqlWpChanger/index
     */
    public function actionIndexWP()
    {
        $tables = [
            'merc_banner',
            'wp_comments',
            'wp_fresh_slider',
            'wp_options',
            'wp_popover',
            'wp_postmeta',
            'wp_posts',
            'wp_prli_clicks',
            'wp_prli_links',
            'wp_usermeta',
            'wp_users',
            'wp_wfHits',
            'wp_yoast_seo_links',
            'xf_conversation_message',
            'xf_data_registry',
            'xf_error_log',
            'xf_option',
            'xf_phrase',
            'xf_phrase_compiled',
            'xf_post',
            'xf_profile_post',
            'xf_profile_post_comment',
            'xf_search_index',
            'xf_session',
            'xf_style',
            'xf_style_property',
            'xf_template',
            'xf_template_compiled',

            // Wrong definition of serialized data!
            //'xf_user_news_feed_cache',

            'xf_user_profile',
            'xf_widget',
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
                        if (FALSE != hlpSuperUnSerialize($strInit)) {
                            $isSerialized  = 'YES';
                            $data          = (new DataPlayer())->serializedArraySearchReplace($strReplace, $s1old, $s1New);
                            $strReplace    = $data->strResControl;
                            $tChangesCount += $data->tCount;
                            $data          = (new DataPlayer())->serializedArraySearchReplace($strReplace, $s2old, $s2New);
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
                        $reportstring                = hlpStrRemoveEnters($reportstring);
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

    public function actionDangerousChanging()
    {
        ;
    }
}
