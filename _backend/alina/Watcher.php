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

    protected function __construct()
    {
        $this->mIP       = new watch_ip();
        $this->mBROWSER  = new watch_browser();
        $this->mURL_PATH = new watch_url_path();
        $this->mVISIT    = new watch_visit();
    }
    #endregion Singleton
    ##################################################
    #region Watch
    protected        $mIP;
    protected        $mBROWSER;
    protected        $mURL_PATH;
    protected        $mVISIT;
    protected static $state_VISIT_LOGGED = FALSE;

    public function logVisitsToDb()
    {
        //ToDo: better Store Procedure
        if (AlinaCFG('logVisitsToDb')) {
            if (!static::$state_VISIT_LOGGED) {
                #####
                $this->mBROWSER->upsertByUniqueFields([
                    'user_agent' => Request::obj()->BROWSER,
                ]);
                $this->mIP->upsertByUniqueFields([
                    'ip' => Request::obj()->IP,
                ]);
                $this->mURL_PATH->upsertByUniqueFields([
                    'url_path' => Request::obj()->URL_PATH,
                ]);
                $this->mVISIT->insert([
                    'ip_id'       => $this->mIP->id,
                    'browser_id'  => $this->mBROWSER->id,
                    'url_path_id' => $this->mURL_PATH->id,
                ]);
                #####
                $this->firewall();
                #####
                static::$state_VISIT_LOGGED = TRUE;
            }
        }

        return $this;
    }
    #endregion Watch
    ##################################################
    #region Firewall
    public function firewall()
    {
        $this->per10seconds();
    }

    protected function per10seconds()
    {
        $browserId = $this->mBROWSER->id;
        $ipId      = $this->mIP->id;
        $per10secs = $this->mVISIT
            ->q()
            ->where([
                'browser_id' => $browserId,
                'ip_id'      => $ipId,
                ['visited_at', '>', ALINA_TIME - 10],
            ])
            ->count();
        if ($per10secs > AlinaCFG('watcher/maxPer10secs')) {
            Message::set('Are you trying to DDOS me?', [], 'alert alert-danger');
            throw new \ErrorException('DDOS');
        }
    }
    #endregion Firewall
    ##################################################
}
