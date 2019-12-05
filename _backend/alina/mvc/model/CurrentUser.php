<?php

namespace alina\mvc\model;

use alina\cookie;
use alina\message;
use alina\session;
use alina\utils\Data;
use alina\utils\Sys;

class CurrentUser
{
    ##################################################
    #region SingleTon
    static protected $currKey = 'CurrentUser';
    /**@var user */
    protected $USER = NULL;

    protected function __construct()
    {
        $this->USER = new user();
        session::set(static::$currKey, NULL);
    }

    static protected $inst = NULL;

    /**
     * @return static
     */
    static public function obj()
    {
        if (!static::$inst) {
            static::$inst = new static();
        }

        return static::$inst;
    }
    #endregion SingleTon
    ##################################################
    public function LogIn($conditions)
    {
        $this->getByConditions($conditions);
        if ($this->USER->id) {
            session::set(static::$currKey, $this->USER->id);
            $this->newToken();

            return $this;
        } else {
            return FALSE;
        }
    }

    public function LogInByToken($id, $token)
    {
        return $this->LogIn([
                'id'        => $id,
                'authtoken' => $token,
            ]
        );
    }

    /**
     * @param array $conditions
     * @return static
     */
    protected function getByConditions($conditions)
    {
        $this->USER->getOneWithReferences($conditions);

        return $this;
    }

    protected function newToken()
    {
        $u = $this->USER;
        $a = $u->attributes;
        if (isset($a->date_int_authtoken_expires)) {
            message::set($a->date_int_authtoken_expires);
            message::set($a->date_int_authtoken_expires - ALINA_TIME);
            if ($a->date_int_authtoken_expires - ALINA_TIME > ALINA_MIN_TIME_DIFF_SEC) {
                //cookie::set('authtoken', $a->authtoken);

                return $this;
            }
        }

        $at = [
            $a->id,
            $a->mail,
            $a->password,
            ALINA_TIME,
        ];
        $at = md5(implode('', $at));
        $u->updateById([
            'id'                         => $a->id,
            'authtoken'                  => $at,
            'date_int_authtoken_expires' => ALINA_AUTH_EXPIRES,
        ]);
        cookie::set('authtoken', $at);

        return $this;
    }

    ##################################################
    public function LogOut()
    {
        cookie::delete('authtoken');
        session::delete(static::$currKey);
    }

    ##################################################
    public function hasRole($role){
        return $this->USER->hasRole($role);
    }
    public function hasPerm($perm){
        return $this->USER->hasPerm($perm);
    }

    public function isLoggedIn()
    {
        $id = $this->USER->id ?: session::get(static::$currKey);
        if ($id && $id > 0) {
            if (isset($_COOKIE['authtoken'])) {
                $token = $_COOKIE['authtoken'];

                return $this->LogInByToken($id, $token);
            }
        }

        return $id;
    }
}
