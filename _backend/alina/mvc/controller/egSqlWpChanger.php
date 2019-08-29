<?php

namespace alina\mvc\controller;

use alina\mvc\model\DataPlayer;
use alina\mvc\view\html as htmlAlias;
use alina\utils\db\mysql\DbManager;
use PDO;

class egSqlWpChanger
{
    /**
     * @route http://alinazero:8080/egSqlWpChanger/index
     */
    public function actionIndex()
    {
        $tables = [
            'test',
            ////////////////////////////////////
            ////////////////////////////////////
            ////////////////////////////////////
            'wp_wfHits',
            'wp_usermeta',
            'wp_users',
            'xf_error_log',
            'xf_session',
            'xf_template',
            'xf_template_compiled',
            ////////////////////////////////////
            ////////////////////////////////////
            ////////////////////////////////////
            // 'wp_posts',
            //  'wp_postmeta',
            // 'merc_banner',
            // 'wp_commentmeta',
            // 'wp_comments',
            // 'wp_fresh_slider',
            // 'wp_options',
            // 'wp_popover',
            // 'wp_prli_clicks',
            // 'wp_prli_links',
            // 'wp_yoast_seo_links',
            // 'xf_conversation_message',
            // 'xf_data_registry',
            // 'xf_option',
            // 'xf_phrase',
            // 'xf_phrase_compiled',
            // 'xf_post',
            // 'xf_profile_post',
            // 'xf_profile_post_comment',
            // 'xf_search_index',
            // 'xf_style',
            // 'xf_style_property',
            // 'xf_user_news_feed_cache',
            // 'xf_user_profile',
            // 'xf_widget',
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
