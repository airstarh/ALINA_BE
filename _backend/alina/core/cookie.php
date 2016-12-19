<?php

namespace alina;


class cookie
{
    public        $name;
    public        $value     = '';
    public        $expire;
    public        $path      = '/';
    public        $domain    = NULL;
    public        $secure    = FALSE;
    public        $httponly  = FALSE;
    public static $past      = ALINA_COOKIE_PAST;
    public static $justAdded = [];

    protected function __construct()
    {
        $this->expire = ALINA_TIME + 60 * 60 * 24 * 10; // 10 days)
    }

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

    #region Fasade.
    static public function getCookieNameByStringPath($stringPath, $delimiter = '/')
    {
        // Prepare $name string.
        $pathArray = explode($delimiter, $stringPath);
        $name      = array_shift($pathArray);
        $name .= '[' . implode('][', $pathArray) . ']';

        return $name;
    }

    static public function setPath($stringPath, $value, $expire = NULL, $delimiter = '/', $path = '/', $domain = NULL, $secure = FALSE, $httponly = FALSE)
    {
        $name            = static::getCookieNameByStringPath($stringPath, $delimiter);
        $_this           = new static;
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
                setArrayValue($stringPath, $value, $_COOKIE);
            }
        }

        return $apply;
    }

    static public function deletePath($stringPath, $delimiter = '/')
    {
        $cookieFamilyName = static::getCookieNameByStringPath($stringPath, $delimiter);

        // Look into Just Added paths.
        foreach (static::$justAdded as $cookieFullName) {
            if (startsWith($cookieFullName, $cookieFamilyName)) {
                $apply = static::delete($cookieFullName);
                if ($apply) {
                    unsetArrayPath($stringPath, $_COOKIE, $delimiter);
                }
            }
        }

        // Look into earlier set cookies.
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
            foreach ($cookies as $cPair) {
                $cNameValue     = explode('=', $cPair);
                $cookieFullName = trim($cNameValue[0]);

                if (startsWith($cookieFullName, $cookieFamilyName)) {
                    $apply = static::delete($cookieFullName);
                    if ($apply) {
                        unsetArrayPath($stringPath, $_COOKIE, $delimiter);
                    }
                }
            }
        }
    }

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
                $_COOKIE[$name] = $value;
            }
        }

        return $apply;
    }

    static public function delete($name)
    {
        $apply = static::set($name, NULL, static::$past);
        if ($apply) {
            unset($_COOKIE[$name]);
        }

        return $apply;
    }

    static public function exists($path)
    {
        return arrayHasPath($path, $_COOKIE);
    }
    #endregion Fasade.
}