<?php

namespace alina\mvc\model;

use alina\cookie;
use alina\Message;
use alina\session;
use alina\traits\Singleton;
use alina\utils\Data;
use alina\utils\Request;
use alina\utils\Sys;

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
    #####
    protected $state_AUTHORIZATION_PASSED = FALSE;
    protected $state_USER_DEFINED         = FALSE;
    public    $msg                        = [];

    protected function __construct()
    {
        $this->USER               = new user();
        $this->LOGIN              = new login();
        $this->device_ip          = Request::obj()->IP;
        $this->device_browser_enc = Request::obj()->BROWSER_enc;
        $this->authorize();
    }
    #endregion SingleTon
    ##################################################
    #region LogIn

    protected function identify($login, $password)
    {
        $this->state_USER_DEFINED         = FALSE;
        $this->state_AUTHORIZATION_PASSED = FALSE;
        if ($this->isLoggedIn()) {
            $this->msg[] = 'You are already Logged-in';

            return FALSE;
        }
        $conditions = [
            'mail'     => $login,
            'password' => $password,
        ];
        $this->defineUSER($conditions);
        if (empty($this->USER->id)) {
            $this->msg[] = 'User not found';

            return FALSE;
        }

        $this->id = $this->USER->id;
        $this->buildToken();
        if ($this->authorize()) {
            $this->msg[] = '$this->authorize()';
            $this->rememberAuthInfo();

            return $this;
        }

        return FALSE;
    }

    protected function authenticate()
    {
        $id    = $this->discoverId();
        $token = $this->discoverToken();

        if (empty($id)) {
            return FALSE;
        }

        if (empty($token)) {
            return FALSE;
        }

        $this->LOGIN->getOne([
            'user_id' => $id,
            'token'   => $token,
        ]);

        if (empty($this->LOGIN->id)) {
            $this->msg[] = 'Not Logged-in';

            return FALSE;
        }

        $la = $this->LOGIN->attributes;
        if ($la->browser_enc != $this->device_browser_enc) {
            $this->msg[] = 'Not Logged-in on this browser';
        }

        if ($la->ip != $this->device_ip) {
            $this->msg[] = 'User changed network';
        }
        #####
        $conditions = [
            "{$this->USER->alias}.{$this->USER->pkName}" => $id,
        ];
        $this->defineUSER($conditions);

        if (empty($this->USER->id)) {
            $this->msg[] = 'User does not exist';

            return FALSE;
        }

        $ua = $this->USER->attributes;
        if ($ua->banned_till > ALINA_TIME) {
            $this->msg[] = 'User is banned';

            return FALSE;
        }

        $this->id    = $this->USER->id;
        $this->token = $this->LOGIN->attributes->token;

        return TRUE;
    }

    protected function authorize()
    {
        if ($this->state_AUTHORIZATION_PASSED) {
            return TRUE;
        }
        #####
        $id              = $this->discoverId();
        $token           = $this->discoverToken();
        $ip              = $this->device_ip;
        $browser_enc     = $this->device_browser_enc;
        $data            = [
            'user_id'     => $id,
            'token'       => $token,
            'ip'          => $ip,
            'browser_enc' => $browser_enc,
            'expires_at'  => ALINA_AUTH_EXPIRES,
            'lastentered' => ALINA_TIME,
        ];
        $isAuthenticated = $this->authenticate();
        if ($isAuthenticated) {
            $this->LOGIN->updateById($data);
            $this->state_AUTHORIZATION_PASSED = TRUE;

            return TRUE;
        } else {
            if (
                !empty($this->token)
                &&
                !empty($this->id)
            ) {
                $this->LOGIN->upsertByUniqueFields($data);
                $this->state_AUTHORIZATION_PASSED = TRUE;

                return TRUE;
            }
        }

        return FALSE;
    }

    public function LogInByPass($mail, $password)
    {
        if (!Data::isValidMd5($password)) {
            $password = md5($password);
        }

        return $this->identify($mail, $password);
    }
    #endregion LogIn
    ##################################################
    #region LogOut
    public function LogOut()
    {
        if ($this->isLoggedIn()) {
            $this->forgetAuthInfo();
        }

        return FALSE;
    }
    #endregion LogOut
    ##################################################
    #region Register
    public function Register($vd)
    {
        $u = $this->USER;
        //ToDo: Add Browser
        //ToDo: Add other data
        $vd->ip               = Sys::getUserIp();
        $vd->date_int_created = ALINA_TIME;
        $u->insert($vd);
        if (isset($u->id)) {
            $ur = new _BaseAlinaModel(['table' => 'rbac_user_role']);
            $ur->insert([
                'user_id' => $u->id,
                //TODo: Hardcoded, 5-servants
                'role_id' => 5,
            ]);
            if (isset($ur->id)) {
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
        return $this->authorize();
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
        if (empty($id)) {
            $id = session::get(static::KEY_USER_ID);
        }
        if (empty($id)) {
            $id = cookie::get(static::KEY_USER_ID);
        }
        if (empty($id)) {
            $id = Request::obj()->tryHeader(static::KEY_USER_ID);
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
        if (empty($token)) {
            $token = session::get(static::KEY_USER_TOKEN);
        }
        if (empty($token)) {
            $token = cookie::get(static::KEY_USER_TOKEN);
        }
        if (empty($token)) {
            $token = Request::obj()->tryHeader(static::KEY_USER_TOKEN);
        }
        $this->token = $token;

        return $token;
    }

    protected function buildToken()
    {
        $u           = $this->USER;
        $ua          = $u->attributes;
        $tokenSource = [
            $ua->id,
            $ua->mail,
            $ua->password,
            ALINA_TIME,
        ];
        $token       = md5(implode('', $tokenSource));
        $this->token = $token;

        return $token;
    }

    public function attributes()
    {
        unset($this->USER->attributes->password);

        return $this->USER->attributes;
    }

    protected function rememberAuthInfo()
    {
        #####
        cookie::set(static::KEY_USER_TOKEN, $this->token);
        cookie::set(static::KEY_USER_ID, $this->id);
        #####
        session::set(static::KEY_USER_TOKEN, $this->token);
        session::set(static::KEY_USER_ID, $this->id);
        #####
        header(implode(': ', [
            static::KEY_USER_TOKEN,
            $this->token,
        ]));
        header(implode(': ', [
            static::KEY_USER_ID,
            $this->id,
        ]));
        #####
        $id          = $this->discoverId();
        $token       = $this->discoverToken();
        $ip          = $this->device_ip;
        $browser_enc = $this->device_browser_enc;
        #####
        $this->LOGIN->upsertByUniqueFields([
            'user_id'     => $id,
            'ip'          => $ip,
            'browser_enc' => $browser_enc,
            'token'       => $token,
            'expires_at'  => ALINA_AUTH_EXPIRES,
            'lastentered' => ALINA_TIME,
        ]);

        return $this;
    }

    protected function forgetAuthInfo()
    {
        if ($this->isLoggedIn()) {
            #####
            cookie::delete(static::KEY_USER_TOKEN);
            cookie::delete(static::KEY_USER_ID);
            #####
            session::delete(static::KEY_USER_TOKEN);
            session::delete(static::KEY_USER_ID);
            #####
            $this->LOGIN->deleteById($this->LOGIN->id);
            #####
            $this->state_USER_DEFINED  = FALSE;
            $this->state_AUTHORIZATION_PASSED = FALSE;
            $this->id                         = NULL;
            $this->token                      = NULL;
            #####
            $this->LOGIN = new login();
            $this->USER  = new user();
        }
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

    protected function defineUSER($conditions)
    {
        if ($this->state_USER_DEFINED) {
            return $this->USER;
        }
        $this->USER->getOneWithReferences($conditions);
        $this->state_USER_DEFINED = TRUE;

        return $this->USER;
    }
    #endregion Utils
    ##################################################
}
