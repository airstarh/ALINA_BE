<?php

namespace alina;

use alina\mvc\model\_BaseAlinaModel;
use alina\mvc\model\watch_banned_browser;
use alina\mvc\model\watch_banned_ip;
use alina\mvc\model\watch_banned_visit;
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
        #####
        $this->firewallByBannedIp();
        $this->firewallByBannedBrowser();
        #####
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
        $this->firewallByBannedVisit();
        $this->firewallByRequestsAmount();
        $this->firewallPostRequest();
    }

    protected function firewallByRequestsAmount()
    {
        $per10secs = $this->countRequestsPerSeconds(10);
        if ($per10secs > AlinaCFG('watcher/maxPer10secs')) {
            (new watch_banned_visit())->upsertByUniqueFields([
                'ip_id'      => $this->mIP->id,
                'browser_id' => $this->mBROWSER->id,
            ]);
            Message::setDanger('Are you trying to DDOS me?');
            throw new \ErrorException('DDOS');
        }
    }

    protected function firewallPostRequest()
    {
        if (Request::isPostPutDelete($post)) {
            if (
                !isset(Request::obj()->POST->form_id)
                ||
                empty(Request::obj()->POST->form_id)
            ) {
                $msg = 'Invalid post data';
                Message::setDanger($msg);
                throw new \Exception($msg);
            }
        }
    }

    protected function firewallByBannedIp()
    {
        $m   = new watch_banned_ip();
        $res = $m
            ->q()
            ->where([
                'ip' => Request::obj()->IP,
            ])
            ->first();
        if ($res) {
            $msg = 'Your IP is banned';
            Message::setDanger($msg, []);
            throw new \ErrorException($msg);
        }
    }

    protected function firewallByBannedBrowser()
    {
        $m   = new watch_banned_browser();
        $res = $m
            ->q()
            ->where([
                'enc' => Request::obj()->BROWSER_enc,
            ])
            ->first();
        if ($res) {
            $msg = 'Your browser is banned';
            Message::setDanger($msg, []);
            throw new \ErrorException($msg);
        }
    }

    protected function firewallByBannedVisit()
    {
        $m   = new watch_banned_visit();
        $res = $m
            ->q()
            ->where([
                'ip_id'      => $this->mIP->id,
                'browser_id' => $this->mBROWSER->id,
            ])
            ->first();
        if ($res) {
            $msg = 'You are completely banned';
            Message::setDanger($msg);
            throw new \ErrorException($msg);
        }
    }

    #endregion Firewall
    ##################################################
    #region Utils
    protected function countRequestsPerSeconds($seconds)
    {
        $browserId = $this->mBROWSER->id;
        $ipId      = $this->mIP->id;
        $res       = $this->mVISIT
            ->q()
            ->where([
                'browser_id' => $browserId,
                'ip_id'      => $ipId,
                ['method', '!=', 'GET'],
                ['visited_at', '>', ALINA_TIME - $seconds],
            ])
            //->whereIn('method', ['POST', 'PUT', 'DELETE'])
            ->orderBy('id', 'desc')
            ->limit(10000)
            ->count();

        return $res;
    }
    #endregion Utils
    ##################################################
}
