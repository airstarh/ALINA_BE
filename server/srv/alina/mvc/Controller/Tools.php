<?php

namespace alina\mvc\Controller;

use alina\GlobalRequestStorage;
use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\file;
use alina\mvc\Model\watch_visit;
use alina\mvc\View\html as htmlAlias;
use alina\Utils\Data;
use alina\Utils\Request;
use alina\Utils\Sys;
use stdClass;

class Tools
{
    /**
     * @route /tools/SerializedDataEditor
     */
    public function actionSerializedDataEditor()
    {
        ##################################################
        $vd = (object) [
                    'form_id'         => __FUNCTION__,
                    'strSource'       => '',
                    'mixedSource'     => '',
                    'strRes'          => '',
                    'mixedRes'        => [],
                    'mixedResControl' => [],
                    'strResControl'   => '',
                    'strFrom'         => '',
                    'strTo'           => '',
                    'tCount'          => 0,
        ];
        $data = new stdClass();

        ##################################################
        if (Request::isPost($post)) {
            $p         = $post;
            $vd        = Data::mergeObjects($vd, $p);
            $strFrom   = $vd->strFrom;
            $strTo     = $vd->strTo;
            $strSource = $vd->strSource;
            $data      = Data::serializedDataSearchReplace($strSource, $strFrom, $strTo);
        }
        ##################################################
        GlobalRequestStorage::obj()->set('pageTitle', 'PHP-Serialized Data Editor online');
        $vd = Data::mergeObjects($vd, $data);
        \AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutWide));

        return $this;
    }

    # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

    /**
     * @file _backend/alina/mvc/template/CtrlDataTransformations/actionJson.php
     */
    public function actionJsonSearchReplaceBeautify()
    {
        $str       = @file_get_contents(\ALINA_WEB_PATH . '/mockups/json.000.json') ?? '{}';
        $strSource = Data::hlpGetBeautifulJsonString($str);
        ##################################################
        $vd = (object) [
                    'form_id'           => __FUNCTION__,
                    'strSource'         => $strSource,
                    'strFrom'           => '',
                    'strTo'             => '',
                    'strRes'            => '',
                    'mxdJsonDecoded'    => '',
                    'mxdResJsonDecoded' => '',
                    'tCount'            => 0,
        ];
        $data = new stdClass();

        ##################################################
        if (Request::isPost($post)) {
            $p         = $post;
            $vd        = Data::mergeObjects($vd, $p);
            $strSource = $vd->strSource;
            $strFrom   = $vd->strFrom;
            $strTo     = $vd->strTo;
            $data      = Data::jsonSearchReplace($strSource, $strFrom, $strTo);
        }
        ##################################################
        GlobalRequestStorage::obj()->set('pageTitle', 'JSON Search-Replace-Beautify online');
        $vd = Data::mergeObjects($vd, $data);
        \AlinaEcho((new htmlAlias())->page($vd, htmlAlias::$htmLayoutWide));

        return $this;
    }

    # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # # #

    public function actionRouteWhiteList()
    {
        // $res = Sys::getWhiteListController();
        $a = 4 / 0;

        $maxPossible = 10;
        $ip          = '172.18.0.1';
        $seconds     = (60 * 60 * 500);
        $mVISIT      = new watch_visit();
        $res         = $mVISIT
            ->q()
            ->where([
                ['ban_point','>', 1],
                'browser_enc' => Request::obj()->BROWSER_enc,
                'ip'          => $ip,
                // 'user_id'     => CurrentUser::obj()::id(),
                // ['method', '!=', 'GET'],
                ['visited_at', '>', ALINA_TIME - $seconds],
            ])
            ->selectRaw('ip, user_id, browser_enc, SUM(ban_point) as total_ban_point')
            ->groupBy('ip', 'user_id', 'browser_enc')
            ->limit($maxPossible + 100)
            ->first()
        ;


        \AlinaEchoDraft($res);
    }
}
