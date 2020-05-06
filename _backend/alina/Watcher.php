<?php

namespace alina;

use alina\mvc\model\watch_banned_browser;
use alina\mvc\model\watch_banned_ip;
use alina\mvc\model\watch_banned_visit;
use alina\mvc\model\watch_browser;
use alina\mvc\model\watch_fools;
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
        $this->firewallFools();
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
                ##################################################
                $this->firewallByBannedVisit();
                ##################################################
                $this->mVISIT->insert([]);
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
        $this->firewallByRequestsAmount();
    }

    protected function firewallByRequestsAmount()
    {
        $per10secs = $this->countRequestsPerSeconds(10);
        if ($per10secs > AlinaCFG('watcher/maxPer10secs')) {
            $this->banVisit();
            $msg = 'Are you trying to DDOS me?';
            AlinaReject(FALSE, 403, $msg);
            exit;
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
            AlinaReject(FALSE, 403, $msg);
            exit;
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
            AlinaReject(FALSE, 403, $msg);
            exit;
        }
    }

    protected function firewallByBannedVisit()
    {
        $mBannedVisits = new watch_banned_visit();
        $res           = $mBannedVisits
            ->q()
            ->where([
                'ip'          => Request::obj()->IP,
                'browser_enc' => Request::obj()->BROWSER_enc,
            ])
            ->first();
        if ($res) {
            $msg = 'You are completely banned';
            AlinaReject(FALSE, 403, $msg);
            exit;
        }
    }

    protected function firewallFools()
    {
        if (
            (
                Request::has('alinafool', $alinafool)
                &&
                $alinafool == 1
            )
            ||
            empty(Request::obj()->DOMAIN)
            ||
            empty(Request::obj()->BROWSER)
            ||
            (Request::isPostPutDelete()
                &&
                (
                    !isset(Request::obj()->POST->form_id)
                    ||
                    empty(Request::obj()->POST->form_id)
                )
            )
        ) {
            (new watch_fools())->insert([]);
            $msg = 'fuck you';
            AlinaReject(FALSE, 403, $msg);
            exit;
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
                'browser_enc' => Request::obj()->BROWSER_enc,
                'ip'          => Request::obj()->IP,
                ['method', '!=', 'GET'],
                ['visited_at', '>', ALINA_TIME - $seconds],
            ])
            ->limit(10000)
            ->count();

        return $res;
    }

    ##################################################
    #region Ban
    public function banIp($ip = NULL, $reason = 'spam')
    {
        if (empty($ip)) {
            $ip = Request::obj()->IP;
        }
        (new watch_banned_ip())->upsertByUniqueFields([
            'ip'     => $ip,
            'reason' => $reason,
        ]);
    }

    public function banBrowser($browser_enc = NULL, $reason = 'spam')
    {
        if (empty($browser_enc)) {
            $browser_enc = Request::obj()->BROWSER_enc;
        }
        (new watch_banned_browser())->upsertByUniqueFields([
            'enc'    => $browser_enc,
            'reason' => $reason,
        ]);
    }

    public function banVisit($ip = NULL, $browser_enc = NULL, $reason = 'spam')
    {
        if (empty($ip)) {
            $ip = Request::obj()->IP;
        }
        if (empty($browser_enc)) {
            $browser_enc = Request::obj()->BROWSER_enc;
        }
        (new watch_banned_visit())->upsertByUniqueFields([
            'ip'          => $ip,
            'browser_enc' => $browser_enc,
            'reason'      => $reason,
        ]);
    }
    #endregion Ban
    ##################################################
    #endregion Utils
    ##################################################
}
