<?php

namespace alina\mvc\Model;

use alina\AppCookie;
use alina\Message;
use alina\traits\Singleton;
use alina\Utils\Data;
use alina\Utils\Obj;
use alina\Utils\Request;

class CurrentUser
{
    ##################################################
    #region SingleTon
    use Singleton;

    const KEY_USER_ID    = 'uid';
    const KEY_USER_TOKEN = 'token';
    static protected user $USER;
    protected login       $LOGIN;
    protected string      $device_ip;
    protected string      $device_browser_enc;
    ##################################################
    static protected bool $state_AUTHORIZATION_PASSED  = false;
    static protected bool $state_AUTHORIZATION_SUCCESS = false;
    ##################################################
    public $msg = [];

    protected function __construct()
    {
        $this->reset();
        $this->authorize();
        if (static::$state_AUTHORIZATION_SUCCESS) {
            $this->upsertLogin(static::$USER->id);
        }
    }
    #endregion SingleTon
    ##################################################
    #region LogIn

    /**
     * Is Logged In?
     * Grant or Deny access.
     */
    protected function authorize()
    {
        if (static::$state_AUTHORIZATION_PASSED) {
            return static::$state_AUTHORIZATION_SUCCESS;
        }
        $isAuthenticated = $this->discoverLogin();
        if ($isAuthenticated) {
            static::$state_AUTHORIZATION_SUCCESS = true;
        }
        static::$state_AUTHORIZATION_PASSED = true;
        return static::$state_AUTHORIZATION_SUCCESS;
    }

    public function LogInByPass($mail, $password)
    {
        if ($this->discoverLogin()) {
            $this->msg[] = 'You are already Logged-in';
            return false;
        }

        if (!Data::isValidMd5($password)) {
            $password = md5($password);
        }

        $conditions = [
            'mail'     => $mail,
            'password' => $password,
        ];

        if ($this->loginProcess($conditions)) {
            return $this->authorize();
        }

        return false;
    }

    protected function loginProcess($conditions)
    {
        $this->reset();
        static::$USER->getOneWithReferences($conditions);
        if (static::$USER->id) {
            $this->upsertLogin(static::$USER->id);
            return true;
        }

        # validate
        if (empty(static::$USER->id)) {
            $this->msg[] = 'Incorrect credentials';
        }

        return false;
    }

    /**
     * Just check if Request contains Auth Data.
     * No database data is changed here.
     */
    protected function discoverLogin()
    {
        $userId   = $this->discoverUserId();
        $oldToken = $this->discoverToken();
        #####
        $this->LOGIN->getOne([
            ['user_id', '=', $userId],
            ['token', '=', $oldToken],
            ['expires_at', '>', ALINA_TIME],
        ]);
        if ($this->LOGIN->id) {
            $uId = static::$USER->alias;
            $uPk = static::$USER->pkName;
            static::$USER->getOneWithReferences([
                "{$uId}.{$uPk}" => $userId,
            ]);
            if (static::$USER->id) {
                return true;
            }
        }

        return false;
    }

    protected function discoverUserId()
    {
        $id = null;
        if (empty($id)) {
            $id = static::$USER->id;
        }
        if (empty($id)) {
            $id = AppCookie::get(static::KEY_USER_ID);
        }
        if (empty($id)) {
            $id = Request::obj()->tryHeader(static::KEY_USER_ID);
        }
        if (!is_numeric($id)) {
            $id = null;
        }

        return $id;
    }

    protected function discoverToken()
    {
        $token = null;
        if (empty($token)) {
            $token = $this->LOGIN->attributes->token;
        }
        if (empty($token)) {
            $token = AppCookie::get(static::KEY_USER_TOKEN);
        }
        if (empty($token)) {
            $token = Request::obj()->tryHeader(static::KEY_USER_TOKEN);
        }
        if (strlen($token) < 10) {
            $token = null;
        }

        return $token;
    }

    protected function upsertLogin($uid)
    {
        $data = $this->buildLoginData($uid);
        $this->LOGIN->upsertByUniqueFields($data, [['user_id', 'browser_enc']]);
        $this->setTokenOnClient(static::$USER->id, $this->LOGIN->attributes->token);
    }

    protected function setTokenOnClient($uid, $token)
    {
        #####
        AppCookie::set(static::KEY_USER_TOKEN, $token);
        AppCookie::set(static::KEY_USER_ID, $uid);
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

        return true;
    }

    #endregion LogIn
    ##################################################
    #region LogOut
    public function LogOut()
    {
        if ($this->isLoggedIn()) {
            return $this->forgetAuthInfo();
        }
        return false;
    }

    protected function forgetAuthInfo()
    {
        #####
        $this->LOGIN->deleteById($this->LOGIN->id);
        #####
        AppCookie::delete(static::KEY_USER_TOKEN);
        AppCookie::delete(static::KEY_USER_ID);
        #####
        header_remove(static::KEY_USER_ID);
        header_remove(static::KEY_USER_TOKEN);
        #####
        $this->resetDiscoveredData();
        $this->resetStates();

        #####
        return true;
    }

    #endregion LogOut
    ##################################################
    #region Register
    public function Register($vd)
    {
        $this->resetMsg();
        $u               = static::$USER;
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
    #region RESET
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
        static::$USER = new user();
        $this->LOGIN  = new login();

        return $this;
    }

    protected function resetStates()
    {
        static::$state_AUTHORIZATION_PASSED  = false;
        static::$state_AUTHORIZATION_SUCCESS = false;

        return $this;
    }

    protected function resetMsg()
    {
        $this->msg = [];

        return $this;
    }
    #endregion RESET
    ##################################################
    #region States
    public static function id()
    {
        return static::$USER->id;
    }

    public function attributes()
    {
        $res        = Obj::deepClone(static::$USER->attributes);
        $res->token = $this->LOGIN->attributes->token;
        unset($res->password);

        return $res;
    }


    public function name()
    {
        $res = static::$USER->attributes->mail;
        if (empty($res)) {
            $res = 'Not Logged-in';
        }

        return $res;
    }

    public function hasRole($role)
    {
        if ($this->isLoggedIn()) {
            return static::$USER->hasRole($role);
        }

        return false;
    }

    public function hasPerm($perm)
    {
        if ($this->isLoggedIn()) {
            return static::$USER->hasPerm($perm);
        }

        return false;
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

        return false;
    }

    public function isModerator()
    {
        if ($this->isLoggedIn()) {
            return $this->hasRole('MODERATOR');
        }

        return false;
    }

    public function isPriveleged()
    {
        if ($this->isLoggedIn()) {
            return $this->hasRole('PRIVILEGED');
        }

        return false;
    }

    public function isAdminOrModerator()
    {
        return $this->isAdmin() || $this->isModerator();
    }

    #endregion States
    ##################################################
    #region Utils


    protected function buildToken()
    {
        if (
            Request::obj()->AJAX
            &&
            !empty($this->LOGIN->attributes->token)
        ) {
            return $this->LOGIN->attributes->token;
        }
        $u           = static::$USER;
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

    protected function buildLoginData($uid)
    {
        $newToken = $this->buildToken();
        $data     = [
            'user_id'     => $uid,
            'token'       => $newToken,
            'ip'          => $this->device_ip,
            'browser_enc' => $this->device_browser_enc,
            'expires_at'  => ALINA_AUTH_EXPIRES,
            'lastentered' => ALINA_TIME,
        ];

        return $data;
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
