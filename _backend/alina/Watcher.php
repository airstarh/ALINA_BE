<?php

namespace alina;

use alina\mvc\model\watch_browser;
use alina\mvc\model\watch_ip;
use alina\mvc\model\watch_url_path;
use alina\mvc\model\watch_visit;
use alina\traits\Singleton;
use alina\utils\Request;

class Watcher
{
    #region Singleton
    use Singleton;

    protected function __construct() { }
    #endregion Singleton
    ##################################################
    #region Watch
    protected        $IP_model;
    protected        $BROWSER_model;
    protected        $URL_PATH_model;
    protected        $VISIT_model;
    protected static $logVisitsToDb_done = FALSE;

    public function logVisitsToDb()
    {
        //ToDo: better Store Procedure
        if (AlinaCFG('logVisitsToDb')) {
            if (!static::$logVisitsToDb_done) {
                $this->IP_model       = new watch_ip();
                $this->BROWSER_model  = new watch_browser();
                $this->URL_PATH_model = new watch_url_path();
                $this->VISIT_model    = new watch_visit();
                #####
                $this->BROWSER_model->upsertByUniqueFields([
                    'user_agent' => Request::obj()->BROWSER,
                ]);
                $this->IP_model->upsertByUniqueFields([
                    'ip' => Request::obj()->IP,
                ]);
                $this->URL_PATH_model->upsertByUniqueFields([
                    'url_path' => Request::obj()->URL_PATH,
                ]);
                $this->VISIT_model->insert([
                    'ip_id'        => $this->IP_model->id,
                    'browser_id'   => $this->BROWSER_model->id,
                    'url_path_id'  => $this->URL_PATH_model->id,
                    'query_string' => Request::obj()->QUERY_STRING,
                    //'visited_at'   => 'use default',
                    //'cookie_key'   => 'use default',
                    //'user_id'      => 'use default',
                ]);
                #####
                static::$logVisitsToDb_done = TRUE;
            }
        }

        return $this;
    }
    #endregion Watch
    ##################################################
}
