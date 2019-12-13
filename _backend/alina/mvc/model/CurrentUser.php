<?php

namespace alina\mvc\model;

use alina\cookie;
use alina\message;
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
    public static $keyUserId    = 'uid';
    public static $keyUserToken = 'token';
    public        $id           = NULL;
    protected     $token        = NULL;
    /**@var user */
    protected $USER = NULL;
    /**@var login */
    protected $LOGIN = NULL;
    protected $device_ip;
    protected $device_browser_enc;
    #####
    protected $state_AUTHORIZATION_PASSED = FALSE;

    protected function __construct()
    {
        $this->USER               = new user();
        $this->LOGIN              = new login();
        $this->device_ip          = Request::obj()->IP;
        $this->device_browser_enc = Request::obj()->BROWSER_enc;
    }
    #endregion SingleTon
    ##################################################
    #region LogIn

    protected function authenticate($login, $password)
    {

    }

    protected function identify($login, $password)
    {
        #####
        if ($this->isLoggedIn()) {
            message::set('You are already Logged-in');
            return $this;
        }
        #####
        $conditions = [
            'mail'     => $login,
            'password' => $password,
        ];
        $this->USER->getOneWithReferences($conditions);
        if (empty($this->USER->id)) {
            return FALSE;
        }

        $this->id    = $this->USER->id;
        $this->token = $this->buildToken();

        $this->rememberAuthInfo();
        $this->authorize();

        return $this;
    }

    protected function authorize()
    {
        if ($this->state_AUTHORIZATION_PASSED) {
            return TRUE;
        }
        #####
        if ($this->validateAuthority()) {
            #####
            $id          = $this->discoverId();
            $token       = $this->discoverToken();
            $ip          = $this->device_ip;
            $browser_enc = $this->device_browser_enc;
            #####
            $this->LOGIN->updateById([
                'user_id'     => $id,
                'ip'          => $ip,
                'browser_enc' => $browser_enc,
                'token'       => $token,
                'expires_at'  => ALINA_AUTH_EXPIRES,
                'lastentered' => ALINA_TIME,
            ]);
            $this->state_AUTHORIZATION_PASSED = TRUE;

            return TRUE;
        }
        #####
        $this->state_AUTHORIZATION_PASSED = TRUE;

        return FALSE;
    }

    protected function validateAuthority()
    {
        if ($this->LOGIN->id) {
            if ($this->USER->id) {
                return TRUE;
            }
        }

        $id    = $this->discoverId();
        $token = $this->discoverToken();

        $this->LOGIN->getOne([
            'user_id' => $id,
            'token'   => $token,
        ]);

        if (empty($this->LOGIN->id)) {
            message::set('Authority failed.');

            return FALSE;
        }

        $la = $this->LOGIN->attributes;
        if ($la->browser_enc != $this->device_browser_enc) {
            message::set('Not Logged-in on this browser');
        }

        if ($la->ip != $this->device_ip) {
            message::set('User changed network');
        }
        #####

        $this->USER->getOneWithReferences([
            "{$this->USER->alias}.{$this->USER->pkName}" => $id,
        ]);

        if (empty($this->USER->id)) {
            message::set('User does not exist');

            return FALSE;
        }

        $ua = $this->USER->attributes;
        if ($ua->banned_till > ALINA_TIME) {
            message::set('User is banned');

            return FALSE;
        }

        return TRUE;
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
            $this->LOGIN->deleteById($this->LOGIN->id);
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
                message::set('Registration has passed successfully!');
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
            $id = session::get(static::$keyUserId);
        }
        if (empty($id)) {
            $id = cookie::get(static::$keyUserId);
        }
        if (empty($id)) {
            $id = Request::obj()->tryHeader(static::$keyUserId);
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
            $token = session::get(static::$keyUserToken);
        }
        if (empty($token)) {
            $token = cookie::get(static::$keyUserToken);
        }
        if (empty($token)) {
            $token = Request::obj()->tryHeader(static::$keyUserToken);
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

        return $token;
    }

    public function attributes()
    {
        return $this->USER->attributes;
    }

    protected function rememberAuthInfo()
    {
        #####
        cookie::set(static::$keyUserToken, $this->token);
        cookie::set(static::$keyUserId, $this->id);
        #####
        session::set(static::$keyUserToken, $this->token);
        session::set(static::$keyUserId, $this->id);
        #####
        header(implode(': ', [
            static::$keyUserToken,
            $this->token,
        ]));
        header(implode(': ', [
            static::$keyUserId,
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
            cookie::delete(static::$keyUserToken);
            cookie::delete(static::$keyUserId);
            #####
            session::delete(static::$keyUserToken);
            session::delete(static::$keyUserId);
            #####
            //ToDO Delete from login table
        }
    }

    #endregion Utils
    ##################################################
}
