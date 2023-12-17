<?php

namespace alina;

use alina\mvc\Model\error_log;
use alina\mvc\Model\watch_banned_browser;
use alina\mvc\Model\watch_banned_ip;
use alina\mvc\Model\watch_banned_visit;
use alina\mvc\Model\watch_browser;
use alina\mvc\Model\watch_fools;
use alina\mvc\Model\watch_ip;
use alina\mvc\Model\watch_url_path;
use alina\mvc\Model\watch_visit;
use alina\traits\Singleton;
use alina\Utils\Request;

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
        #####
        $this->firewallFools();
        $this->firewallByBannedIp();
        $this->firewallByBannedBrowser();
        $this->firewallByBannedVisit();
        $this->firewallByRequestsAmount();
        $this->firewallFgp();
        #####
    }
    #endregion Singleton
    ##################################################
    #region Watch
    protected        $mIP;
    protected        $mBROWSER;
    protected        $mURL_PATH;
    protected        $mVISIT;
    protected static $state_VISIT_LOGGED = false;

    public function logVisitsToDb()
    {
        #####
        #####
        //ToDo: better Store Procedure
        if (AlinaCfg('logVisitsToDb')) {
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
                $this->mVISIT->insert([]);
                #####
                static::$state_VISIT_LOGGED = true;
            }
        }

        return $this;
    }
    #endregion Watch
    ##################################################
    #region Firewall
    protected function firewallByRequestsAmount()
    {
        if (!Request::isPostPutDelete()) {
            return;
        }
        $maxPer10secs = AlinaCfg('watcher/maxPer10secs');
        $per10secs    = $this->countRequestsPerSeconds(10, $maxPer10secs);
        if ($per10secs > $maxPer10secs) {
            $this->banVisit();
            $msg = 'Are you trying to DDOS me?';
            AlinaReject(false, 403, $msg);
            exit;
        }
    }

    protected function firewallByBannedIp()
    {
        if (!Request::isPostPutDelete()) {
            return;
        }
        $m   = new watch_banned_ip();
        $res = $m
            ->q()
            ->where([
                'ip' => Request::obj()->IP,
            ])
            ->first()
        ;
        if ($res) {
            $msg = 'Your IP is banned';
            AlinaReject(false, 403, $msg);
            exit;
        }
    }

    protected function firewallByBannedBrowser()
    {
        if (!Request::isPostPutDelete()) {
            return;
        }
        $m   = new watch_banned_browser();
        $res = $m
            ->q()
            ->where([
                'enc' => Request::obj()->BROWSER_enc,
            ])
            ->first()
        ;
        if ($res) {
            $msg = 'Your browser is banned';
            AlinaReject(false, 403, $msg);
            exit;
        }
    }

    protected function firewallByBannedVisit()
    {
        if (!Request::isPostPutDelete()) {
            return;
        }
        $mBannedVisits = new watch_banned_visit();
        $res           = $mBannedVisits
            ->q()
            ->where([
                'ip'          => Request::obj()->IP,
                'browser_enc' => Request::obj()->BROWSER_enc,
            ])
            ->first()
        ;
        if ($res) {
            $msg = 'You are completely banned';
            AlinaReject(false, 403, $msg);
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
            AlinaReject(false, 403, $msg);
            exit;
        }
    }

    protected function firewallFgp()
    {
        if (Request::obj()->AJAX) {
            if (Request::obj()->tryHeader('fgp', $fgp)) {
                if (empty($fgp)) {
                    (new error_log())->insert(['error_text' => 'Suspicious request. Empty fgp',]);
                    AlinaReject(null, 403);
                    exit;
                }
                if ($fgp !== Request::obj()->BROWSER) {
                    $orig = Request::obj()->BROWSER;
                    //Message::setDanger(Request::obj()->BROWSER);
                    //Message::setDanger($fgp);
                    //AlinaReject(NULL, 403, 'Suspicious request');
                    (new error_log())->insert(['error_text' => "Suspicious request. Bad fgp ---{$orig}--- ||| ---{$fgp}---",]);
                }
            }
        }
    }
    #endregion Firewall
    ##################################################
    #region Utils
    protected function countRequestsPerSeconds($seconds, $maxPossible = 10000)
    {
        $res = $this->mVISIT
            ->q()
            ->where([
                'browser_enc' => Request::obj()->BROWSER_enc,
                'ip'          => Request::obj()->IP,
                ['method', '!=', 'GET'],
                ['visited_at', '>', ALINA_TIME - $seconds],
            ])
            ->limit($maxPossible + 100)
            ->count()
        ;

        return $res;
    }
    #endregion Utils
    ##################################################
    #region Ban
    public function banIp($ip = null, $reason = 'spam')
    {
        if (empty($ip)) {
            $ip = Request::obj()->IP;
        }
        (new watch_banned_ip())->upsertByUniqueFields([
            'ip'     => $ip,
            'reason' => $reason,
        ]);
    }

    public function banBrowser($browser_enc = null, $reason = 'spam')
    {
        if (empty($browser_enc)) {
            $browser_enc = Request::obj()->BROWSER_enc;
        }
        (new watch_banned_browser())->upsertByUniqueFields([
            'enc'    => $browser_enc,
            'reason' => $reason,
        ]);
    }

    public function banVisit($ip = null, $browser_enc = null, $reason = 'spam')
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
}
