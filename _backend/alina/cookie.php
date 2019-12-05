<?php

namespace alina;

//ToDO: Completely rewrite,
use alina\utils\Arr;
use alina\utils\Str;

class cookie
{
    ##################################################
    #region Init
    protected static $past      = ALINA_COOKIE_PAST;
    protected static $justAdded = [];
    protected           $name;
    protected           $value     = '';
    protected           $expire;
    protected           $path      = '/';
    protected           $domain    = NULL;
    protected           $secure    = FALSE;
    protected           $httponly  = FALSE;

    protected function __construct()
    {
        $this->expire = ALINA_AUTH_EXPIRES;
    }
    #endregion Init
    ##################################################
    #region SET
    static public function set($name, $value, $expire = NULL, $path = '/', $domain = NULL, $secure = FALSE, $httponly = FALSE)
    {
        $_this           = new static;
        $_this->name     = $name;
        $_this->value    = $value;
        $_this->expire   = (!empty($expire)) ? $expire : $_this->expire;
        $_this->path     = $path;
        $_this->domain   = $domain;
        $_this->secure   = $secure;
        $_this->httponly = $httponly;
        $apply           = $_this->apply();
        if ($apply) {
            if ($_this->expire > ALINA_TIME) {
                Arr::setArrayValue($name, $value, $_COOKIE);
            }
        }

        return $apply;
    }

    static public function setPath($stringPath, $value, $expire = NULL, $delimiter = '/', $path = '/', $domain = NULL, $secure = FALSE, $httponly = FALSE)
    {
        $_this           = new static;
        $name            = static::buildNameByPath($stringPath, $delimiter);
        $_this->name     = $name;
        $_this->value    = $value;
        $_this->expire   = (!empty($expire)) ? $expire : $_this->expire;
        $_this->path     = $path;
        $_this->domain   = $domain;
        $_this->secure   = $secure;
        $_this->httponly = $httponly;

        $apply = $_this->apply();
        if ($apply) {
            if ($_this->expire > ALINA_TIME) {
                Arr::setArrayValue($stringPath, $value, $_COOKIE);
            }
        }

        return $apply;
    }
    #endregion SET
    ##################################################
    #region GET
    static public function get($stringPath, $delimiter = '/')
    {
        return Arr::getArrayValue($stringPath, $_COOKIE, $delimiter);
    }
    #endregion GET
    ##################################################
    #region Delete
    static public function deletePath($stringPath, $delimiter = '/')
    {
        $cookieFamilyName = static::buildNameByPath($stringPath, $delimiter);

        // Look into Just Added paths.
        foreach (static::$justAdded as $cookieFullName) {
            if (Str::startsWith($cookieFullName, $cookieFamilyName)) {
                $apply = static::delete($cookieFullName);
                if ($apply) {
                    Arr::unsetArrayPath($stringPath, $_COOKIE, $delimiter);
                }
            }
        }

        // Look into earlier set cookies.
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cPair) {
                $cNameValue     = explode('=', $cPair);
                $cookieFullName = trim($cNameValue[0]);

                if (Str::startsWith($cookieFullName, $cookieFamilyName)) {
                    $apply = static::delete($cookieFullName);
                    if ($apply) {
                        Arr::unsetArrayPath($stringPath, $_COOKIE, $delimiter);
                    }
                }
            }
        }
    }

    static public function delete($name)
    {
        $apply = static::set($name, NULL, static::$past);
        if ($apply) {
            unset($_COOKIE[$name]);
        }

        return $apply;
    }
    #endregion Delete
    ##################################################
    #region Utils
    public function apply()
    {
        $process = setcookie(
            $this->name,
            $this->value,
            $this->expire,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httponly
        );
        if ($this->expire > ALINA_TIME) {
            static::$justAdded[] = $this->name;
        }

        return $process;
    }

    static public function buildNameByPath($stringPath, $delimiter = '/')
    {
        // Prepare $name string.
        $pathArray = explode($delimiter, $stringPath);
        $name      = array_shift($pathArray);
        if (!empty($pathArray)) {
            $name .= '[' . implode('][', $pathArray) . ']';
        }

        return $name;
    }

    static public function exists($path, $delimiter = '/')
    {
        return Arr::arrayHasPath($path, $_COOKIE, $delimiter);
    }
    #endregion Utils
    ##################################################
}
