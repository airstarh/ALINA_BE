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
        ###//VA: EMERGENT self::$ENABLED = AlinaCfg('logVisitsToDb') && ! AlinaAccessIfAdmin();
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
        $this->firewallByBanPoint();
        $this->firewallFools();
        $this->firewallByBannedIp();
        $this->firewallByBannedBrowser();
        $this->firewallByBannedVisit();
        $this->firewallByRequestsAmount();
        $this->firewallFgp();

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

        if (! Request::obj()->isPostPutDelete()) {
            return;
        }
        $maxPer10secs = AlinaCfg('watcher/maxPer10secs');
        $per10secs    = $this->countRequestsPerSeconds(10, $maxPer10secs);

        if ($per10secs > $maxPer10secs) {
            $msg = 'DDoS last 10 seconds.';
            $this->banVisit($msg);
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallByBannedIp()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::obj()->isPostPutDelete()) {
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
            $msg = 'Your IP is banned.';
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallByBannedBrowser()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::obj()->isPostPutDelete()) {
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
            $msg = 'Your browser is banned.';
            AlinaReject(null, 403, $msg);
        }
    }

    private function firewallByBannedVisit()
    {
        if (! self::$ENABLED) {
            return;
        }

        if (! Request::obj()->isPostPutDelete()) {
            return;
        }
        $mBannedVisits = new watch_banned_visit();
        $res           = $mBannedVisits
            ->q()
            ->where([
                'ip'          => Request::obj()->IP,
                'user_id'     => CurrentUser::obj()->id(),
                'browser_enc' => Request::obj()->BROWSER_enc,
            ])
            ->first()
        ;

        if ($res) {
            $msg = 'You are banned.';
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
                Request::obj()->isPostPutDelete()
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
                $msg = "Suspicious. Wrong FGP.";
                $this->mVisitAddBanPoints(1);
                AlinaReject(null, 403, $msg);
            }
        }
    }

    public function firewallByBanPoint()
    {
        $deltaT    = 60;
        $maxPoints = 10;
        $points    = $this->getBanPointsLastSeconds($deltaT);

        if ($points >= $maxPoints) {
            $this->mVisitAddBanPoints($points > 100 ? 100 : $points);
            $msg = "DDoS 1 minute.";
            AlinaReject(null, 403, $msg);
        }

        $deltaT    = 60 * 60;
        $maxPoints = 250;
        $points    = $this->getBanPointsLastSeconds($deltaT);

        if ($points >= $maxPoints) {
            $this->mVisitAddBanPoints($points > 100 ? 100 : $points);
            $msg = "DDoS 1 hour.";
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
                'user_id'     => CurrentUser::obj()::id(),
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

    private function getBanPointsLastSeconds(int $seconds = 10)
    {
        $res = $this->mVISIT
            ->q()
            ->where([
                ['ban_point','>', 0],
                'browser_enc' => Request::obj()->BROWSER_enc,
                'ip'          => Request::obj()->IP,
                'user_id'     => CurrentUser::obj()::id(),
                ['visited_at', '>=', ALINA_TIME - $seconds],
            ])
            ->selectRaw('ip, user_id, browser_enc, SUM(ban_point) as total_ban_point')
            ->groupBy('ip', 'user_id', 'browser_enc')
            ->first()
            ?? new stdClass()
        ;

        return (int) ($res->total_ban_point ?? 0);
    }

    public function id()
    {
        return $this->mVISIT->id ?? 0;
    }
    #endregion Utils
    ##################################################
    #region Ban
    public function banIp($reason = 'spam', $ip = null)
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

    public function banBrowser($reason = 'spam', $browser_enc = null)
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

    public function banVisit($reason = 'spam', $ip = null, $browser_enc = null, $user_id = null)
    {
        if (! self::$ENABLED) {
            return;
        }

        $ip          = $ip          ?? Request::obj()->IP;
        $browser_enc = $browser_enc ?? Request::obj()->BROWSER_enc;
        $user_id     = $user_id     ?? CurrentUser::obj()->id();

        (new watch_banned_visit())->upsertByUniqueFields([
            'ip'          => $ip,
            'browser_enc' => $browser_enc,
            'user_id'     => $user_id,
            'reason'      => $reason,
        ]);
    }
    #endregion Ban
    ##################################################
}
