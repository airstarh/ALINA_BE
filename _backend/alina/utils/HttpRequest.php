<?php

namespace alina\utils;
/**
 * https://www.php.net/manual/ru/function.curl-setopt.php
 */
class HttpRequest
{
    ##########################################
    #region Request
    private        $ch;
    private string $reqMethod         = 'GET';
    private int    $flagMethodMutator = 0;
    /**
     * Documentation: https://developer.mozilla.org/ru/docs/Web/HTTP/Methods
     */
    private array $methods         = [
        'Method Name' => 'Does Method causes Mutation?',
        'GET'         => 0,
        'POST'        => 1,
        'PUT'         => 1,
        'PATCH'       => 1,
        'DELETE'      => 0,
        'OPTIONS'     => 0,
        'HEAD'        => 0,
        'CONNECT'     => 0,
        'TRACE'       => 0,
    ];
    public string $reqUri          = '';
    public array  $reqGet          = [];
    private       $reqFields       = [];
    private int   $flagFieldsRaw   = 0;
    private array $reqHeaders      = [
        //'Content-Type' => 'multipart/form-data; charset=utf-8',
    ];
    private array $reqCookie       = [];
    private array $arrUriInterface = [
        'scheme'   => '',
        'host'     => '',
        'port'     => '',
        'user'     => '',
        'pass'     => '',
        'path'     => '',
        'query'    => '',
        'fragment' => '',
    ];
    #endregion Request
    ##########################################
    #region Response
    public array  $curlInfo                = [];
    public string $resUrl                  = '';
    public string $respBody                = '';
    public array  $respHeaders             = [];
    public array  $respHeadersStructurized = [];
    public int    $redirections            = 0;
    #endregion Response
    ##########################################
    #region INIT
    public function __construct($uri = NULL, $method = NULL, $query = NULL, $fields = NULL, $headers = NULL, $cookie = NULL)
    {
        $this->ch = curl_init();
        if ($uri) $this->setReqUri($uri);
        if ($query) $this->addGet($query);
        if ($method) $this->setReqMethod($method);
        if ($fields) $this->setFields($fields);
        if ($headers) $this->addHeaders($headers);

        return $this;
    }
    #endregion INIT
    ##########################################
    #region Facade Stuff
    /**
     * Sets:
     * $this->>reqUri:string
     * $this->>reqGet:[]
     */
    public function setReqUri(string $str): HttpRequest
    {
        $parsedUri = parse_url($str);
        ##############################
        #region Extract and add URI
        $this->arrUriInterface = array_merge($this->arrUriInterface, $parsedUri);
        $this->reqUri          = $this->unParseUrl([
            'scheme' => $this->arrUriInterface['scheme'],
            'host'   => $this->arrUriInterface['host'],
            'port'   => $this->arrUriInterface['port'],
            'user'   => $this->arrUriInterface['user'],
            'pass'   => $this->arrUriInterface['pass'],
            'path'   => $this->arrUriInterface['path'],
            // Exclude:
            // 'query'    => '',
            // 'fragment' => '',
        ]);
        #endregion Extract and add URI
        ##############################
        #region Extract and add Get
        $arrGet = [];
        $strGet = (isset($parsedUri['query'])) ? $parsedUri['query'] : '';
        parse_str($strGet, $arrGet);
        $this->setGet($arrGet);
        #endregion Extract and add Get
        ##############################
        return $this;
    }

    /**
     * Adds:
     * $this->reqHeaders:[]
     */
    public function addHeaders(array $arr): HttpRequest
    {
        $this->reqHeaders = array_merge($this->reqHeaders, (array)$arr);

        return $this;
    }

    /**
     * Adds:
     * $this->reqCookie:[]
     */
    public function addCookie($arr): HttpRequest
    {
        $this->reqCookie = array_merge($this->reqCookie, (array)$arr);

        return $this;
    }

    /**
     * Sets:
     * $this->reqGet:[]
     */
    public function setGet(array $arr): HttpRequest
    {
        $this->reqGet = [];
        $this->addGet($arr);

        return $this;
    }

    /**
     * Adds:
     * $this->reqGet:[]
     */
    public function addGet(array $arr): HttpRequest
    {
        $this->reqGet = array_merge($this->reqGet, (array)$arr);

        return $this;
    }

    public function setFlagFieldsRaw($v): HttpRequest
    {
        $this->flagFieldsRaw = $v;

        return $this;
    }

    /**
     * Sets:
     * $this->reqFields:[]|string
     *
     * @param mixed $mixed
     */
    public function setFields($mixed, $method = 'POST'): HttpRequest
    {
        //#####
        if (empty($mixed)) return $this;
        //#####
        if ($this->flagFieldsRaw) {
            $this->reqFields = $mixed;
        }
        else {
            $this->reqFields = array_merge($this->reqFields, (array)$mixed);
        }
        if (!empty($this->reqFields) && $this->reqMethod === 'GET') {
            $this->setReqMethod($method);
        }

        return $this;
    }

    /**
     * Sets:
     * $this->method:string
     * $this->flagMethodMutator:Boolean|Int
     * CURLOPT_CUSTOMREQUEST
     */
    public function setReqMethod(string $method): HttpRequest
    {
        $this->reqMethod         = strtoupper($method);
        $this->flagMethodMutator = $this->methods[$this->reqMethod] ?? 0;

        return $this;
    }

    public function exe(): HttpRequest
    {
        $ch      =& $this->ch;
        $url     = $this->prepareUrlAndGet();
        $headers = $this->prepareHeaders();
        $cookie  = $this->prepareCookie();
        $fields  = $this->prepareFields();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->reqMethod);
        //curl_setopt($ch, CURLOPT_USERAGENT, 'VA Services');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 11);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, [$this, 'callback_CURLOPT_HEADERFUNCTION']);
        // POST PUT PATCH
        if (!empty($fields)) {
            if ($this->flagFieldsRaw) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
                //$this->addHeaders(['Content-Type' => 'text/plain',]);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        }
        // Set Headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if (!empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        //EXECUTION
        $this->respBody = curl_exec($ch);
        if ($this->respBody == FALSE) {
            $this->respBody = curl_error($ch);
        }
        $this->curlInfo = curl_getinfo($ch);
        curl_close($ch);

        return $this;
    }

    #endregion Facade Stuff
    ##########################################
    #region Request Prepare Staff
    private function prepareUrlAndGet(): string
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

    private function prepareFields()
    {
        $reqFields = $this->reqFields;
        if ($this->flagFieldsRaw) {
            return $reqFields;
        }
        $reqFields = http_build_query($reqFields);

        return $reqFields;
    }

    /**
     * For CURLOPT_HTTPHEADER
     * @return string[]
     */
    private function prepareHeaders(): array
    {
        $arr = $this->reqHeaders;
        $res = [];
        $s   = '';
        foreach ($arr as $k => $v) {
            if (is_numeric($k)) {
                $s = $v;
            }
            else {
                if (is_string($k)) {
                    if (!empty($v)) {
                        $s = "{$k}: {$v}";
                    }
                    else {
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

    /**
     * For CURLOPT_COOKIE
     * @return string
     */
    private function prepareCookie(): string
    {
        $arr = $this->reqCookie;
        $res = [];
        $s   = '';
        foreach ($arr as $k => $v) {
            if (is_numeric($k)) {
                $s = $v;
            }
            else {
                if (is_string($k)) {
                    if (!empty($v)) {
                        $s = "{$k}={$v}";
                    }
                    else {
                        $s = $k;
                    }
                }
            }
            if (!empty($s)) {
                $res[] = $s;
            }
        } #end foreach
        if (!empty($res)) {
            $res = implode('; ', $res);
        }
        else {
            $res = '';
        }

        return $res;
    }

    #endregion Request Prepare Staff
    ##########################################
    #region Utils
    /**
     * Reverse parse_url
     * @link https://stackoverflow.com/a/31691249/3142281
     * @param array $parsedUri
     * @return string
     */
    private function unParseUrl(array $parsedUri): string
    {
        return Url::un_parse_url($parsedUri);
    }

    private function callback_CURLOPT_HEADERFUNCTION($ch, $str): int
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

    public function take($prop)
    {
        return $this->{$prop};
    }
    #endregion Utils
    ##########################################
}
