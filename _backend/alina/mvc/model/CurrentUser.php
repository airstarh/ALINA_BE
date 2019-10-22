<?php

namespace alina\mvc\model;

use alina\message;
use alina\session;
use alina\utils\Data;
use alina\utils\Sys;

class CurrentUser extends user
{
    public function NewAuthToken()
    {
        $a                          = $this->attributes;
        $at                         = [
            $a->id,
            $a->mail,
            $a->password,
            ALINA_TIME,
        ];
        $at                         = md5(implode('', $at));
        $date_int_authtoken_expires = ALINA_TIME + (60 * 10);
        $this->updateById([
            'id'                         => $a->id,
            'authtoken'                  => $at,
            'date_int_authtoken_expires' => $date_int_authtoken_expires,
        ]);

        return $this;
    }

    public function authByToken($authtoken = NULL)
    {
        if (!$authtoken) {
            $authtoken = $this->g('authtoken');
        }
        $attributes = $this->getOneWithReferences([
            'authtoken' => $authtoken,
        ]);

        $this->auth();

        return $this;
    }

    public function auth()
    {
        if (isset($this->id)) {
            if ($this->attributes->date_int_authtoken_expires < ALINA_TIME) {
                session::set('CurrentUser', $this);
            }
        } else {
            message::set('Unable to authorize', [], 'alert alert-danger');
        }

        return $this;
    }
}
