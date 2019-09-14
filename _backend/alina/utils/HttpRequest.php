<?php

namespace alina\utils;

/**
 * https://www.php.net/manual/ru/function.curl-setopt.php
 */
class HttpRequest
{
    public  $reqUri       = '';
    public  $reqGet       = [];
    private $reqPost      = [];
    private $reqIsPostRaw = 0;
    private $reqHeaders   = [
        //'Content-Type' => 'multipart/form-data; charset=utf-8',
    ];
    private $reqUriParsed = [
        'scheme'   => '',
        'host'     => '',
        'port'     => '',
        'user'     => '',
        'pass'     => '',
        'path'     => '',
        'query'    => '',
        'fragment' => '',
    ];
    ##########################################
    #region Response
    public $curlInfo;
    public $resUrl;
    public $respBody;
    public $respHeaders             = [];
    public $respHeadersStructurized = [];
    public $redirections            = 0;
    #endregion Response
    ##########################################
    #region Facade Stuff
    public function setReqUri($str)
    {
        $parsedUri = parse_url($str);
        #region Extract and add Get
        $strGet    = (isset($parsedUri['query'])) ? $parsedUri['query'] : '';
        $arrGet    = [];
        parse_str($strGet, $arrGet);
        $this->addGet($arrGet);
        #endregion Extract and add Get

        #region Extract and add URI
        $this->reqUriParsed = array_merge($this->reqUriParsed, $parsedUri);
        $this->reqUri       = $this->un_parse_url([
            'scheme' => $this->reqUriParsed['scheme'],
            'host'   => $this->reqUriParsed['host'],
            'port'   => $this->reqUriParsed['port'],
            'user'   => $this->reqUriParsed['user'],
            'pass'   => $this->reqUriParsed['pass'],
            'path'   => $this->reqUriParsed['path'],
            // 'query'    => '',
            // 'fragment' => '',
        ]);

        #endregion Extract and add URI

        return $this;
    }

    /**
     * Reverse parse_url
     * @link https://stackoverflow.com/a/31691249/3142281
     * @param array $parsedUri
     * @return string
     */
    private function un_parse_url(array $parsedUri)
    {
        $get = function ($key) use ($parsedUri) {
            return isset($parsedUri[$key]) ? $parsedUri[$key] : NULL;
        };

        $pass      = $get('pass');
        $user      = $get('user');
        $userinfo  = (!empty($pass)) ? "$user:$pass" : $user;
        $port      = $get('port');
        $scheme    = $get('scheme');
        $query     = $get('query');
        $fragment  = $get('fragment');
        $authority =
            (!empty($userinfo) ? "$userinfo@" : '') .
            $get('host') .
            ($port ? ":$port" : '');

        return
            (strlen($scheme) ? "$scheme:" : '') .
            (strlen($authority) ? "//$authority" : '') .
            $get('path') .
            (strlen($query) ? "?$query" : '') .
            (strlen($fragment) ? "#$fragment" : '');
    }

    public function addHeaders($arr)
    {
        $this->reqHeaders = array_merge($this->reqHeaders, (array)$arr);

        return $this;
    }

    public function addGet($arr)
    {
        $this->reqGet = array_merge($this->reqGet, (array)$arr);

        return $this;
    }

    public function addPost($arr)
    {
        if ($this->reqIsPostRaw) {
            $this->reqPost = $arr;

            return $this;
        }
        $this->reqPost = array_merge($this->reqPost, (array)$arr);

        return $this;
    }

    public function setIsPostRaw($v)
    {
        $this->reqIsPostRaw = $v;

        return $this;
    }

    public function exe()
    {
        $url     = $this->forCurlUrlAndGet();
        $headers = $this->forCurlHeaders();
        $post    = $this->forCurlPost();
        $ch      = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 11);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'callbackCURLOPT_HEADERFUNCTION']);

        // POST
        if (!empty($post)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            if ($this->reqIsPostRaw) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        // Set Headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $this->respBody = curl_exec($ch);
        $this->curlInfo = curl_getinfo($ch);
        curl_close($ch);

        return $this;
    }

    #endregion Facade Stuff
    ##########################################
    #region Final Stuff
    private function forCurlUrlAndGet()
    {
        $reqUriClean  = $this->reqUri;
        $get          = http_build_query($this->reqGet);
        $arr          = [
            $reqUriClean,
            empty($get) ? '' : '?',
            $get,
        ];
        $s            = implode('', $arr);
        $this->resUrl = $s;

        return $this->resUrl;
    }

    private function forCurlPost()
    {
        $resPost = $this->reqPost;
        if ($this->reqIsPostRaw) {
            return $resPost;
        }
        $resPost = http_build_query($resPost);

        return $resPost;
    }

    /**
     * For CURLOPT_HTTPHEADER
     */
    private function forCurlHeaders()
    {
        $arr = $this->reqHeaders;
        $res = [];
        $s   = '';
        foreach ($arr as $k => $v) {
            if (is_numeric($k)) {
                $s = $v;
            } else {
                if (is_string($k)) {
                    if (!empty($v)) {
                        $s = "{$k}: {$v}";
                    } else {
                        $s = $k;
                    }
                }
            }
            if (!empty($s)) {
                $res[] = $s;
            }
        } #end foreach

        return $res;
    }

    private function callbackCURLOPT_HEADERFUNCTION($ch, $str)
    {
        //responseHeaderCount
        $counter             = $this->redirections;
        $this->respHeaders[] = $str;

        if (empty(trim($str))) {
            $this->redirections++;

            return strlen($str);
        }

        $a                                           = explode(':', $str, 2);
        $n                                           = (isset($a[0])) ? trim($a[0]) : 'UNDEFINED';
        $v                                           = (isset($a[1])) ? trim($a[1]) : 'UNDEFINED';
        $this->respHeadersStructurized[$counter][$n] = $v;

        return strlen($str);
    }
    #endregion Final Stuff
    ##########################################

}
