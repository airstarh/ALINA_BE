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
    public $id = null;
    ##################################################
    #region SingleTon
    use Singleton;
    static protected $currKey  = 'CurrentUser';
    static protected $tokenKey = 'authtoken';
    /**@var user */
    protected $USER  = NULL;
    protected $LOGIN = NULL;

    protected function __construct()
    {
        $this->USER  = new user();
        $this->LOGIN = new login();
        $this->isLoggedIn();
    }
    #endregion SingleTon
    ##################################################
    #region LogIn
    protected function LogIn($conditions)
    {
        $this->getByConditions($conditions);
        if ($this->USER->id) {
            $this->id = $this->USER->id;
            session::set(static::$currKey, $this->USER->id);
            $this->buildToken();

            return $this;
        } else {
            return FALSE;
        }
    }

    public function LogInByPass($mail, $password)
    {
        return $this->LogIn([
                "{$this->USER->alias}.mail"     => $mail,
                "{$this->USER->alias}.password" => md5($password),
            ]
        );
    }

    public function LogInByToken($id, $token)
    {
        return $this->LogIn([
                "{$this->USER->alias}.{$this->USER->pkName}" => $id,
                "{$this->USER->alias}.authtoken"             => $token,
            ]
        );
    }

    #endregion LogIn
    ##################################################
    #region LogOut
    public function LogOut()
    {
        cookie::delete(static::$tokenKey);
        session::delete(static::$currKey);
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
    }
    #endregion Register
    ##################################################
    #region States
    public function hasRole($role)
    {
        return $this->USER->hasRole($role);
    }

    public function hasPerm($perm)
    {
        return $this->USER->hasPerm($perm);
    }

    public function isLoggedIn()
    {
        $id = $this->USER->id;
        if (empty($id)) {
            $id = session::get(static::$currKey);
        }
        if (empty($id)) {
            $id = Request::obj()->hasHeader(static::$tokenKey);
        }
        if ($id && $id > 0) {
            if (isset($_COOKIE[static::$tokenKey])) {
                $token = $_COOKIE[static::$tokenKey];

                return $this->LogInByToken($id, $token);
            }
        }

        return $id;
    }
    #endregion States
    ##################################################
    #region Utils
    /**
     * @param array $conditions
     * @return static
     */
    protected function getByConditions($conditions)
    {
        $this->USER->getOneWithReferences($conditions);

        return $this;
    }

    protected function buildToken()
    {
        $u = $this->USER;
        $a = $u->attributes;
        ##################################################
        if (isset($a->date_int_authtoken_expires) && !empty($a->date_int_authtoken_expires)) {
            if ($a->date_int_authtoken_expires - ALINA_TIME > ALINA_MIN_TIME_DIFF_SEC) {

                return $this;
            }
        }
        ##################################################
        $at = [
            $a->id,
            $a->mail,
            $a->password,
            ALINA_TIME,
        ];
        $at = md5(implode('', $at));
        $u->updateById([
            'id'                         => $a->id,
            static::$tokenKey            => $at,
            'date_int_authtoken_expires' => ALINA_AUTH_EXPIRES,
            'date_int_lastenter'         => ALINA_AUTH_EXPIRES,
            'ip'                         => Sys::getUserIp(),
        ]);
        cookie::set(static::$tokenKey, $at);

        return $this;
    }

    public function attributes()
    {
        return $this->USER->attributes;
    }
    #endregion Utils
    ##################################################
}
