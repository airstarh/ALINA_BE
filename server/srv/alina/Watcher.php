<?php

namespace alina;

use alina\mvc\Model\watch_banned_browser;
use alina\mvc\Model\watch_banned_ip;
use alina\mvc\Model\watch_banned_visit;
use alina\mvc\Model\watch_browser;
use alina\mvc\Model\watch_fools;
use alina\mvc\Model\watch_visit;
use alina\traits\Singleton;
use alina\Utils\Data;
use alina\Utils\Request;

final class Watcher
{
    #region Singleton
    use Singleton;

    private watch_browser $mBROWSER;
    private watch_visit $mVISIT;
    private static $ENABLED            = true;
    private static $state_VISIT_LOGGED = false;

    private function __construct()
    {
        self::$ENABLED = AlinaCfg('logVisitsToDb');

        if (! self::$ENABLED) {
            return;
        }
        #####
        $this->mBROWSER = new watch_browser();
        $this->mVISIT   = new watch_visit();
        #####
        $this->logVisitsToDb();
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
    public function logVisitsToDb()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! static::$state_VISIT_LOGGED) {
            $this->mBROWSER->upsertByUniqueFields([
                'user_agent' => Request::obj()->BROWSER,
            ]);
            $this->mVISIT->insert([]);
            ##################################################
            static::$state_VISIT_LOGGED = true;
        }

        return $this;
    }
    #endregion Watch
    ##################################################
    #region Firewall
    private function firewallByRequestsAmount()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::isPostPutDelete()) {
            return;
        }
        $maxPer10secs = AlinaCfg('watcher/maxPer10secs');
        $per10secs    = $this->countRequestsPerSeconds(10, $maxPer10secs);

        if ($per10secs > $maxPer10secs) {
            $this->banVisit();
            $msg = 'DDos';
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallByBannedIp()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::isPostPutDelete()) {
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
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallByBannedBrowser()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::isPostPutDelete()) {
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
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallByBannedVisit()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::isPostPutDelete()) {
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
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallFools()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (
            (
                Request::has('alinafool', $alinafool)
                && $alinafool == 1
            )
            || empty(Request::obj()->DOMAIN)
            || empty(Request::obj()->BROWSER)
            || \mb_strlen(Request::obj()->URL_PATH) > 2000
            || (
                Request::isPostPutDelete()
                && (
                    ! isset(Request::obj()->POST->form_id)
                    || empty(Request::obj()->POST->form_id)
                )
            )
        ) {
            (new watch_fools())->insert([]);
            $msg = 'fuck you';
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallFgp()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (Request::obj()->AJAX) {
            $fgpExpected = Request::obj()->BROWSER;
            $fgpFact     = Request::obj()->tryHeader('fgp');

            if ($fgpFact !== $fgpExpected) {
                $msg = "Suspicious. Wrong FGP";
                $this->mVISIT->si('ban_point');
                AlinaReject(null, 403, $msg);
            }
        }
    }
    #endregion Firewall
    ##################################################
    #region Utils
    private function countRequestsPerSeconds($seconds, $maxPossible = 10000)
    {
        if (! self::$ENABLED) {
            return;
        }
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

    public function answer($data)
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! empty($this->mVISIT->id)) {
            $data = Data::toObject($data);

            if (! empty($data->ban_point)) {
                $data->ban_point = $data->ban_point + ($this->mVISIT->attributes->ban_point ?? 0);
            }
            $this->mVISIT->updateById($data);
        }

        return $this;
    }

    public function mVisitAddBanPoints(?int $points = 1)
    {
        if (! self::$ENABLED) {
            return;
        }
        $this->mVISIT->si('ban_point', $points);
    }
    #endregion Utils
    ##################################################
    #region Ban
    public function banIp($ip = null, $reason = 'spam')
    {
        if (! self::$ENABLED) {
            return;
        }

        $ip = $ip ?? Request::obj()->IP;
        (new watch_banned_ip())->upsertByUniqueFields([
            'ip'     => $ip,
            'reason' => $reason,
        ]);
    }

    public function banBrowser($browser_enc = null, $reason = 'spam')
    {
        if (! self::$ENABLED) {
            return;
        }

        $browser_enc = $browser_enc ?? Request::obj()->BROWSER_enc;
        (new watch_banned_browser())->upsertByUniqueFields([
            'enc'    => $browser_enc,
            'reason' => $reason,
        ]);
    }

    public function banVisit($ip = null, $browser_enc = null, $reason = 'spam')
    {
        if (! self::$ENABLED) {
            return;
        }

        $ip          = $ip          ?? Request::obj()->IP;
        $browser_enc = $browser_enc ?? Request::obj()->BROWSER_enc;

        (new watch_banned_visit())->upsertByUniqueFields([
            'ip'          => $ip,
            'browser_enc' => $browser_enc,
            'reason'      => $reason,
        ]);
    }
    #endregion Ban
    ##################################################
}
