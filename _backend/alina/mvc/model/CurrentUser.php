<?php

namespace alina\mvc\model;

use alina\cookie;
use alina\Message;
use alina\traits\Singleton;
use alina\utils\Data;
use alina\utils\Obj;
use alina\utils\Request;

class CurrentUser
{
    ##################################################
    #region SingleTon
    use Singleton;
    const KEY_USER_ID    = 'uid';
    const KEY_USER_TOKEN = 'token';
    public    $id    = NULL;
    protected $token = NULL;
    /**@var user */
    protected $USER = NULL;
    /**@var login */
    protected $LOGIN = NULL;
    protected $device_ip;
    protected $device_browser_enc;
    ##################################################
    protected $state_AUTHORIZATION_PASSED  = FALSE;
    protected $state_AUTHORIZATION_SUCCESS = FALSE;
    protected $state_USER_DEFINED          = FALSE;
    ##################################################
    protected $state_CONSISTANCY_WRONG = FALSE;
    protected $ERR_TOKEN_EXPIRED       = 'ERR_TOKEN_EXPIRED';
    protected $ERR_IP                  = 'ERR_IP';
    protected $ERR_BROWSER             = 'ERR_BROWSER';
    protected $ERR_TOKEN_MISMATCH      = 'ERR_TOKEN_MISMATCH';
    protected $ERR_LOGIN_ID            = 'ERR_LOGIN_ID';
    protected $ERR_USER_ID             = 'ERR_USER_ID';
    ##################################################
    public $msg = [];

    protected function __construct()
    {
        $this->reset();
        $this->authorize();
    }

    protected function reset()
    {
        $this->device_ip          = Request::obj()->IP;
        $this->device_browser_enc = Request::obj()->BROWSER_enc;
        $this->resetDiscoveredData();
        $this->resetStates();
        $this->resetMsg();

        return $this;
    }

    public function resetDiscoveredData()
    {
        $this->id    = NULL;
        $this->token = NULL;
        $this->USER  = new user();
        $this->LOGIN = new login();

        return $this;
    }

    protected function resetStates()
    {
        $this->state_AUTHORIZATION_PASSED  = FALSE;
        $this->state_AUTHORIZATION_SUCCESS = FALSE;
        $this->state_USER_DEFINED          = FALSE;

        return $this;
    }

    protected function resetMsg()
    {
        $this->msg = [];

        return $this;
    }
    #endregion SingleTon
    ##################################################
    #region LogIn
    protected function identify($conditions)
    {
        if ($this->authenticate()) {
            $this->msg[] = 'You are already Logged-in';

            return FALSE;
        }
        #####
        $this->reset();
        #####
        $this->getUSER($conditions);
        if (empty($this->USER->id)) {
            $this->msg[] = 'Incorrect credentials';

            return FALSE;
        }
        $data = $this->buildLoginData();
        $this->LOGIN->upsertByUniqueFields($data);
        $this->getLOGIN($data);

        return $this->rememberAuthInfo($this->USER->id, $this->LOGIN->attributes->token);
    }

    protected function authenticate()
    {
        $id    = $this->discoverId();
        $token = $this->discoverToken();
        #####
        $this->getLOGIN([
            'user_id' => $id,
            'token'   => $token,
        ]);
        if ($this->LOGIN->id) {
            $this->getUSER([
                "{$this->USER->alias}.{$this->USER->pkName}" => $id,
            ]);
        }

        #####
        return $this->analyzeConsistency();
    }

    protected function authorize()
    {
        #####
        if ($this->state_AUTHORIZATION_PASSED) {
            return $this->state_AUTHORIZATION_SUCCESS;
        }
        #####
        $isAuthenticated = $this->authenticate();
        if ($isAuthenticated) {
            $data     = $this->buildLoginData();
            $newToken = $data['token']; // ACCENT
            $this->LOGIN->updateById($data);
            $this->token = $newToken;
            ##########
            $this->state_AUTHORIZATION_SUCCESS = $this->rememberAuthInfo($this->id, $newToken);
        }
        #####
        $this->state_AUTHORIZATION_PASSED = TRUE;

        return $this->state_AUTHORIZATION_SUCCESS;
    }

    public function LogInByPass($mail, $password)
    {
        if (!Data::isValidMd5($password)) {
            $password = md5($password);
        }
        $conditions = [
            'mail'     => $mail,
            'password' => $password,
        ];
        if ($this->identify($conditions)) {
            return $this->authorize();
        }

        return FALSE;
    }
    #endregion LogIn
    ##################################################
    #region LogOut
    public function LogOut()
    {
        if ($this->isLoggedIn()) {
            return $this->forgetAuthInfo();
        }
    }
    #endregion LogOut
    ##################################################
    #region Register
    public function Register($vd)
    {
        $this->resetMsg();
        $u               = $this->USER;
        $vd->created_at  = ALINA_TIME;
        $vd->is_verified = 0;
        $vd->is_deleted  = 0;
        $u->insert($vd);
        if (isset($u->id)) {
            $mUserRole = new rbac_user_role();
            $mUserRole->insert([
                'user_id' => $u->id,
                //ToDo: Hardcoded, 5-servants
                'role_id' => 5,
            ]);
            if (isset($mUserRole->id)) {
                $this->msg[] = 'Registration has passed successfully!';
            }
        }

        return $this;
    }
    #endregion Register
    ##################################################
    #region States
    public function hasRole($role)
    {
        if ($this->isLoggedIn()) {
            return $this->USER->hasRole($role);
        }

        return FALSE;
    }

    public function hasPerm($perm)
    {
        if ($this->isLoggedIn()) {
            return $this->USER->hasPerm($perm);
        }

        return FALSE;
    }

    public function isLoggedIn()
    {
        $res = $this->authorize();

        return $res;
    }

    public function isAdmin()
    {
        if ($this->isLoggedIn()) {
            return $this->hasRole('ADMIN');
        }

        return FALSE;
    }
    #endregion States
    ##################################################
    #region Utils
    protected function discoverId()
    {
        $id = NULL;
        if (empty($id)) {
            $id = $this->USER->id;
        }
        // if (empty($id)) {
        //     //$id = session::get(static::KEY_USER_ID);
        // }
        if (empty($id)) {
            $id = cookie::get(static::KEY_USER_ID);
        }
        if (empty($id)) {
            $id = Request::obj()->tryHeader(static::KEY_USER_ID);
        }
        if (!is_numeric($id)) {
            $id = NULL;
        }
        $this->id = $id;

        return $id;
    }

    protected function discoverToken()
    {
        $token = NULL;
        if (empty($token)) {
            $token = $this->token;
        }
        // if (empty($token)) {
        //     //$token = session::get(static::KEY_USER_TOKEN);
        // }
        if (empty($token)) {
            $token = cookie::get(static::KEY_USER_TOKEN);
        }
        if (empty($token)) {
            $token = Request::obj()->tryHeader(static::KEY_USER_TOKEN);
        }
        if (strlen($token) < 10) {
            $token = NULL;
        }
        $this->token = $token;

        return $token;
    }

    protected function buildToken()
    {
        if (
            Request::obj()->AJAX
            &&
            !empty($this->LOGIN->attributes->token)
        ) {
            return $this->LOGIN->attributes->token;
        }
        $u           = $this->USER;
        $ua          = $u->attributes;
        $tokenSource = [
            $ua->id,
            $ua->mail,
            $ua->password,
            ALINA_TIME,
        ];
        $token       = md5(implode('', $tokenSource));

        return $token;
    }

    protected function buildLoginData()
    {
        $newToken = $this->buildToken();
        $data     = [
            'user_id'     => $this->USER->id,
            'token'       => $newToken,
            'ip'          => $this->device_ip,
            'browser_enc' => $this->device_browser_enc,
            'expires_at'  => ALINA_AUTH_EXPIRES,
            'lastentered' => ALINA_TIME,
        ];

        return $data;
    }

    public function attributes()
    {
        $res        = Obj::deepClone($this->USER->attributes);
        $res->token = $this->token;
        unset($res->password);

        return $res;
    }

    protected function rememberAuthInfo($uid, $token)
    {
        if (!$this->analyzeConsistency()) {
            return FALSE;
        }
        #####
        cookie::set(static::KEY_USER_TOKEN, $token);
        cookie::set(static::KEY_USER_ID, $uid);
        #####
        // session::set(static::KEY_USER_TOKEN, $token);
        // session::set(static::KEY_USER_ID, $uid);
        #####
        header(implode(': ', [
            static::KEY_USER_TOKEN,
            $token,
        ]));
        header(implode(': ', [
            static::KEY_USER_ID,
            $uid,
        ]));

        return TRUE;
    }

    protected function forgetAuthInfo()
    {
        #####
        $this->LOGIN->deleteById($this->LOGIN->id);
        #####
        cookie::delete(static::KEY_USER_TOKEN);
        cookie::delete(static::KEY_USER_ID);
        #####
        // session::delete(static::KEY_USER_TOKEN);
        // session::delete(static::KEY_USER_ID);
        #####
        header_remove(static::KEY_USER_ID);
        header_remove(static::KEY_USER_TOKEN);
        #####
        $this->resetDiscoveredData();
        $this->resetStates();

        #####
        return TRUE;
    }

    public function name()
    {
        $res = $this->USER->attributes->mail;
        if (empty($res)) {
            $res = 'Not Logged-in';
        }

        return $res;
    }

    public function ownsId($id)
    {
        return $this->isLoggedIn() && $this->id === $id;
    }

    protected function getUSER($conditions)
    {
        if ($this->state_USER_DEFINED) {
            return $this->USER;
        }
        $this->USER->getOneWithReferences($conditions);
        $this->id                 = $this->USER->id;
        $this->state_USER_DEFINED = TRUE;

        return $this->USER;
    }

    protected function getLOGIN($conditions)
    {
        $this->LOGIN->getOne($conditions);
        $this->token = $this->LOGIN->attributes->token;

        return $this->LOGIN;
    }

    protected function checkConsistency()
    {
        if (empty($this->LOGIN->id)) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_LOGIN_ID;
            $this->msg[]                   = 'Login undefined';

            return FALSE;
        }
        if ($this->token !== $this->LOGIN->attributes->token) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_TOKEN_MISMATCH;
            $this->msg[]                   = 'Token mismatch';

            return FALSE;
        }
        if (ALINA_TIME >= $this->LOGIN->attributes->expires_at) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_TOKEN_EXPIRED;
            $this->msg[]                   = 'Token expired';

            return FALSE;
        }
        ##################################################
        if (empty($this->USER->id)) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_USER_ID;
            $this->msg[]                   = 'User undefined';

            return FALSE;
        }
        if ($this->id !== $this->USER->id) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_USER_ID;
            $this->msg[]                   = 'User mismatch';

            return FALSE;
        }
        ##################################################
        if ($this->USER->id !== $this->LOGIN->attributes->user_id) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_USER_ID;
            $this->msg[]                   = 'User ID differs from Logged one';

            return FALSE;
        }
        ##################################################
        if ($this->device_ip !== $this->LOGIN->attributes->ip) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_IP;
            $this->msg[]                   = 'IP mismatch';

            return FALSE;
        }
        if ($this->device_browser_enc !== $this->LOGIN->attributes->browser_enc) {
            $this->state_CONSISTANCY_WRONG = $this->ERR_BROWSER;
            $this->msg[]                   = 'Browser mismatch';

            return FALSE;
        }
        ##################################################
        if (ALINA_TIME <= $this->USER->attributes->banned_till) {
            $this->msg[] = 'User banned';

            return FALSE;
        }

        return TRUE;
    }

    protected function analyzeConsistency()
    {
        $consistency = $this->checkConsistency();
        if (!$consistency) {
            switch ($this->state_CONSISTANCY_WRONG) {
                case $this->ERR_TOKEN_EXPIRED:
                    $this->forgetAuthInfo();
                    break;
                case $this->ERR_BROWSER:
                case $this->ERR_IP:
                case $this->ERR_TOKEN_MISMATCH:
                    $this->LOGIN->delete([
                        'user_id' => $this->USER->id,
                    ]);
                    break;
                case $this->ERR_LOGIN_ID:
                case $this->ERR_USER_ID:
                    //ToDo:...
                    break;
            }
        }

        return $consistency;
    }

    public function messages()
    {
        foreach ($this->msg as $i => $v) {
            Message::setInfo($v);
        }
    }
    #endregion Utils
    ##################################################
}
