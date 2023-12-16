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
    protected $id    = null;
    protected $token = null;
    /**@var user */
    protected $USER = null;
    /**@var login */
    protected $LOGIN = null;
    protected $device_ip;
    protected $device_browser_enc;
    ##################################################
    static protected bool $state_AUTHORIZATION_PASSED  = FALSE;
    static protected bool $state_AUTHORIZATION_SUCCESS = FALSE;
    ##################################################
    public $msg = [];

    protected function __construct()
    {
        $this->reset();
        $this->authorize();
        if (static::$state_AUTHORIZATION_SUCCESS){
            $this->updateLoginToken($this->id());
        }
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
        $this->id    = null;
        $this->token = null;
        $this->USER  = new user();
        $this->LOGIN = new login();

        return $this;
    }

    protected function resetStates()
    {
        static::$state_AUTHORIZATION_PASSED  = FALSE;
        static::$state_AUTHORIZATION_SUCCESS = FALSE;

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
            $this->token = $this->LOGIN->attributes->token;
            $this->USER->getOneWithReferences([
                "{$this->USER->alias}.{$this->USER->pkName}" => $userId,
            ]);
            if ($this->USER->id) {
                $this->id = $this->USER->id;
                return true;
            }
        }

        return false;
    }

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
            static::$state_AUTHORIZATION_SUCCESS = TRUE;
        }
        static::$state_AUTHORIZATION_PASSED = TRUE;
        return static::$state_AUTHORIZATION_SUCCESS;
    }

    public function LogInByPass($mail, $password)
    {
        if ($this->discoverLogin()) {
            $this->msg[] = 'You are already Logged-in';

            return FALSE;
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

        return FALSE;
    }

    protected function loginProcess($conditions)
    {
        $this->reset();
        $this->USER->getOneWithReferences($conditions);
        $this->id = $this->USER->id;

        # validate
        if (empty($this->id())) {
            $this->msg[] = 'Incorrect credentials';
            return FALSE;
        }

        $this->updateLoginToken($this->id());
        return true;
    }

    protected function updateLoginToken($uid)
    {
        $data = $this->buildLoginData($uid);
        $this->LOGIN->upsertByUniqueFields($data, [['user_id', 'browser_enc']]);
        $this->token = $this->LOGIN->attributes->token;
        $this->setTokenOnClient($this->USER->id, $this->LOGIN->attributes->token);
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
    public function id()
    {
        return $this->id;
    }

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

    public function isModerator()
    {
        if ($this->isLoggedIn()) {
            return $this->hasRole('MODERATOR');
        }

        return FALSE;
    }

    public function isPriveleged()
    {
        if ($this->isLoggedIn()) {
            return $this->hasRole('PRIVILEGED');
        }

        return FALSE;
    }

    public function isAdminOrModerator()
    {
        return $this->isAdmin() || $this->isModerator();
    }

    #endregion States
    ##################################################
    #region Utils
    protected function discoverUserId()
    {
        $id = null;
        if (empty($id)) {
            $id = $this->USER->id;
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
        $this->id = $id;

        return $id;
    }

    protected function discoverToken()
    {
        $token = null;
        if (empty($token)) {
            $token = $this->token;
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

    public function attributes()
    {
        $res        = Obj::deepClone($this->USER->attributes);
        $res->token = $this->token;
        unset($res->password);

        return $res;
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

        return TRUE;
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
        return $this->isLoggedIn() && $this->id() === $id;
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
