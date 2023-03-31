<?php

namespace alina\utils;
/**
 * https://www.php.net/manual/ru/function.curl-setopt.php
 */
class HttpRequest
{
    ##########################################
    #region Class Adjustments
    private array $log             = [];
    private int   $attemptMax      = 5;
    private int   $attempt         = 1;
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
    private array $arrUrlInterface = [
        'scheme'   => '',
        'host'     => '',
        'port'     => '',
        'user'     => '',
        'pass'     => '',
        'path'     => '',
        'query'    => '',
        'fragment' => '',
    ];
    #endregion Class Adjustments
    ##########################################
    #region Request
    private        $ch;
    private string $reqUrl            = '';
    private string $reqMethod         = 'GET';
    private int    $flagMethodMutator = 0;
    /**
     * Documentation: https://developer.mozilla.org/ru/docs/Web/HTTP/Methods
     */
    private array $reqGet = [];
    /**@var array|string */
    private       $reqFields     = [];
    private int   $flagFieldsRaw = 0;
    private array $reqHeaders    = [
        //'Content-Type' => 'multipart/form-data; charset=utf-8',
    ];
    private array $reqCookie     = [];
    #endregion Request
    ##########################################
    #region Response/Results
    private array  $curlInfo                = [];
    private string $resUrl                  = '';
    private string $respBody                = '';
    private int    $respErrno               = 0;
    private array  $respHeaders             = [];
    private array  $respHeadersStructurized = [];
    private int    $amountLocationsVisited  = 0;
    #endregion Response/Results
    ##########################################
    #region INIT
    public function __construct(
        $uri = NULL, //string
        $method = NULL,//string uppercase
        $query = NULL,//array
        $fields = NULL,//array|string
        $headers = NULL,//array ["Header-Name"=>"Header Value"]
        $cookie = NULL,//array ["Cookie-Name"=>"Cookie Value"]
        $flagFieldsRaw = NULL, // 0|1
        $attemptMax = 5// int
    )
    {
        $this->ch = curl_init();
        if ($attemptMax !== NULL) $this->attemptMax = $attemptMax;
        if ($flagFieldsRaw !== NULL) $this->flagFieldsRaw = $flagFieldsRaw;
        if ($uri) $this->setReqUrl($uri);
        if ($query) $this->addReqGet($query);
        if ($method) $this->setReqMethod($method);
        if ($fields) $this->setReqFields($fields);
        if ($headers) $this->addReqHeaders($headers);
        if ($cookie) $this->addReqCookie($cookie);

        return $this;
    }
    #endregion INIT
    ##########################################
    #region Facade Stuff
    /**
     * Sets:
     * $this->>reqUrl:string
     * $this->>reqGet:[]
     */
    public function setReqUrl(string $str): HttpRequest
    {
        $parsedUri = parse_url($str);
        ##############################
        #region Extract and add URI
        $this->arrUrlInterface = array_merge($this->arrUrlInterface, $parsedUri);
        $this->reqUrl          = $this->unParseUrl([
            'scheme' => $this->arrUrlInterface['scheme'],
            'host'   => $this->arrUrlInterface['host'],
            'port'   => $this->arrUrlInterface['port'],
            'user'   => $this->arrUrlInterface['user'],
            'pass'   => $this->arrUrlInterface['pass'],
            'path'   => $this->arrUrlInterface['path'],
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
        $this->setReqGet($arrGet);
        #endregion Extract and add Get
        ##############################
        return $this;
    }

    /**
     * Sets:
     * $this->reqGet:[]
     */
    public function setReqGet(array $arr): HttpRequest
    {
        $this->reqGet = [];
        $this->addReqGet($arr);

        return $this;
    }

    /**
     * Adds:
     * $this->reqGet:[]
     */
    public function addReqGet(array $arr): HttpRequest
    {
        $this->reqGet = array_merge($this->reqGet, $arr);

        return $this;
    }

    /**
     * For CURLOPT_CUSTOMREQUEST
     * Sets:
     * $this->method:string
     * $this->flagMethodMutator:Boolean|Int
     */
    public function setReqMethod(string $method): HttpRequest
    {
        $this->reqMethod         = strtoupper($method);
        $this->flagMethodMutator = $this->methods[$this->reqMethod] ?? 0;

        return $this;
    }

    /**
     * Adds:
     * $this->reqHeaders:[]
     */
    public function addReqHeaders(array $arr): HttpRequest
    {
        $this->reqHeaders = array_merge($this->reqHeaders, $arr);

        return $this;
    }

    /**
     * Adds:
     * $this->reqCookie:[]
     */
    public function addReqCookie($arr): HttpRequest
    {
        $this->reqCookie = array_merge($this->reqCookie, (array)$arr);

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
     * @param mixed $mixed
     */
    public function setReqFields($mixed, $method = 'POST', $flagFieldsRaw = NULL): HttpRequest
    {
        //#####
        if (empty($mixed)) return $this;
        //#####
        if ($flagFieldsRaw !== NULL) {
            $this->setFlagFieldsRaw($flagFieldsRaw);
        }
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
        do {
            $this->respBody = curl_exec($ch);
            $errno          = curl_errno($ch);
            if ($errno > 0 || !$this->respBody) {
                $this->respBody = curl_error($ch);
                $this->attempt++;
            }
            else break;
        } while ($this->attempt < $this->attemptMax);
        $this->log['attempt']    = $this->attempt;
        $this->log['curl_errno'] = curl_errno($ch);
        $this->log['curl_error'] = curl_error($ch);
        $this->curlInfo          = curl_getinfo($ch);
        curl_close($ch);

        return $this;
    }

    #endregion Facade Stuff
    ##########################################
    #region Request Prepare Staff
    private function prepareUrlAndGet(): string
    {
        $reqUrlClean  = $this->reqUrl;
        $get          = http_build_query($this->reqGet);
        $arr          = [
            $reqUrlClean,
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

        return http_build_query($reqFields);
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
                        $s = "$k: $v";
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
                        $s = "$k=$v";
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

    /**
     * Documentation:
     * @link https://curl.se/libcurl/c/CURLOPT_HEADERFUNCTION.html
     */
    private function callback_CURLOPT_HEADERFUNCTION($ch, $str): int
    {
        $this->log['callback_CURLOPT_HEADERFUNCTION'][] = $str;
        //responseHeaderCount
        $this->respHeaders[]                                              = $str;
        $a                                                                = explode(':', $str, 2);
        $n                                                                = (!empty(trim($a[0]))) ? trim($a[0]) : 'UNDEFINED';
        $v                                                                = (!empty(trim($a[1]))) ? trim($a[1]) : 'UNDEFINED';
        $this->respHeadersStructurized[$this->amountLocationsVisited][$n] = $v;
        if (empty(trim($str))) {
            $this->amountLocationsVisited++;
        }

        return strlen($str);
    }

    public function take($prop)
    {
        return $this->{$prop};
    }

    public function set($prop, ...$value): HttpRequest
    {
        #####
        $commonSetter = function ($obj, $p, $v) {
            $obj->{$p}                     = $v;
            $this->log['commonSetter'][$p] = $v;
        };
        #####
        switch ($prop) {
            /**
             * Block of Properties available for consumers.
             */
            case 'attemptMax':
                call_user_func($commonSetter, $this, $prop, ...$value);
                break;
            case 'flagFieldsRaw':
                call_user_func([$this, 'setFlagFieldsRaw'], ...$value);
                break;
            case 'reqUrl':
                call_user_func([$this, 'setReqUrl'], ...$value);
                break;
            case 'reqMethod':
                call_user_func([$this, 'setReqMethod'], ...$value);
                break;
            case 'reqGet':
                call_user_func([$this, 'addReqGet'], ...$value);
                break;
            case 'reqFields':
                call_user_func([$this, 'setReqFields'], ...$value);
                break;
            case 'reqHeaders':
                call_user_func([$this, 'addReqHeaders'], ...$value);
                break;
            case 'reqCookie':
                call_user_func([$this, 'addReqCookie'], ...$value);
                break;
            /**
             * Block of Read-Only Properties or Properties are changed during the execution.
             */
            case 'ch':
            case 'methods':
            case 'flagMethodMutator':
            case 'arrUriInterface':
            case 'curlInfo':
            case 'resUrl':
            case 'respBody':
            case 'respHeaders':
            case 'respHeadersStructurized':
            case 'amountLocationsVisited':
            case 'attempt':
            case 'log':
                return $this;
        }

        return $this;
    }

    public function report()
    {
        return [
            'REQUEST'  => [
                'reqMethod'     => $this->reqMethod,
                'reqUrl'        => $this->reqUrl,
                'reqGet'        => $this->reqGet,
                'flagFieldsRaw' => $this->flagFieldsRaw,
                'reqFields'     => $this->reqFields,
                'reqHeaders'    => $this->reqHeaders,
                'reqCookie'     => $this->reqCookie,
            ],
            'RESPONSE' => [
                'flagMethodMutator'       => $this->flagMethodMutator,
                'attempt'                 => $this->attempt,
                'amountLocationsVisited'  => $this->amountLocationsVisited,
                'resUrl'                  => $this->resUrl,
                'respBody'                => $this->respBody,
                'respHeaders'             => $this->respHeaders,
                'respHeadersStructurized' => $this->respHeadersStructurized,
                'curlInfo'                => $this->curlInfo,
                'log'                     => $this->log,
            ],
        ];
    }
    #endregion Utils
    ##########################################
}
