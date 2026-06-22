<?php

namespace alina;

use alina\mvc\Model\CurrentUser;
use alina\mvc\Model\watch_banned_browser;
use alina\mvc\Model\watch_banned_ip;
use alina\mvc\Model\watch_banned_visit;
use alina\mvc\Model\watch_browser;
use alina\mvc\Model\watch_fools;
use alina\mvc\Model\watch_visit;
use alina\traits\Singleton;
use alina\Utils\Data;
use alina\Utils\Request;
use stdClass;

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

        $this->mBROWSER = new watch_browser();
        $this->mVISIT   = new watch_visit();
    }

    public function firstStep()
    {
        if (! self::$ENABLED) {
            return;
        }

        $this->logVisitsToDb();
        $this->firewallFools();
        $this->firewallByBannedIp();
        $this->firewallByBannedBrowser();
        $this->firewallByBannedVisit();
        $this->firewallByRequestsAmount();
        $this->firewallFgp();
        $this->firewallByBanPoint();

        return $this;
    }
    #endregion Singleton
    ##################################################
    #region Watch
    private function logVisitsToDb()
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
                && empty(Request::obj()->POST->form_id)
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

    public function firewallByBanPoint()
    {
        $maxPointsLast10sec = 3;
        $maxPointsLast60sec = 10;
        $maxPointsLast1Hour = 20;
        $sec10              = 10;
        $sec60              = 60;
        $hour1              = 60 * 60;
        $banLest10sec       = $this->getBanPointsLastSeconds($sec10);

        if ($banLest10sec > $maxPointsLast10sec) {
            $this->mVisitAddBanPoints($maxPointsLast10sec);
            $msg = 'Ban:'.$banLest10sec;
            AlinaReject(null, 403, $msg);
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

    public function getBanPointsLastSeconds(int $seconds = 10)
    {
        $res = $this->mVISIT
            ->q()
            ->where([
                ['ban_point','>', 0],
                'browser_enc' => Request::obj()->BROWSER_enc,
                'ip'          => Request::obj()->IP,
                'user_id'     => CurrentUser::obj()::id(),
                ['visited_at', '>', ALINA_TIME - $seconds],
            ])
            ->selectRaw('ip, user_id, browser_enc, SUM(ban_point) as total_ban_point')
            ->groupBy('ip', 'user_id', 'browser_enc')
            ->first()
            ?? new stdClass()
        ;

        AlinaDebug($res);

        return (int) ($res->total_ban_point ?? 0);
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
