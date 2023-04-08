<?php

namespace alina\utils;

use Exception;

/**
 * https://www.php.net/manual/ru/function.curl-setopt.php
 * @property HttpRequest::ch resource
 */
class HttpRequest
{
    ##########################################
    #region Class Adjustments
    private array $log             = [];
    private int   $attemptMax      = 1;
    private int   $attempt         = 0;
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
    private        $ch                = NULL;
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
        ###'Pragma' => 'no-cache',
    ];
    private array $reqCookie     = [];
    #endregion Request
    ##########################################
    #region Response/Results
    public array    $curlInfo                = [];
    private string  $resUrl                  = '';
    private string  $respBody                = '';
    private ?object $respBodyObject          = NULL;
    private int     $httpCode                = 0;
    private int     $respErrno               = 0;
    private string  $respErr                 = '';
    private array   $respHeaders             = [];
    private array   $respHeadersStructurized = [];
    private int     $amountLocationsVisited  = 0;
    #endregion Response/Results
    ##########################################
    #region INIT
    public function __construct(
        $uri = NULL,           //string
        $method = NULL,        //string uppercase
        $query = NULL,         //array
        $fields = NULL,        //array|string
        $headers = NULL,       //array ["Header-Name"=>"Header Value"]
        $cookie = NULL,        //array ["Cookie-Name"=>"Cookie Value"]
        $flagFieldsRaw = NULL, // 0|1
        $attemptMax = 1// int
    )
    {
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
    #region SELF CHECK
    static public function selfCheck(): array
    {
        $url  = 'http://www.example.com';
        $http = new static($url);
        $res  = $http->exe()->take('respBody');

        return [
            'report' => $http->report(),
        ];
    }
    #endregion SELF CHECK
    ##########################################
    #region Facade Stuff
    public function setAttemptMax($v): HttpRequest
    {
        $this->attemptMax = $v;

        return $this;
    }

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
        #####
        $closeConnection = function () {
            if ($this->ch && gettype($this->ch) == 'resource') {
                curl_close($this->ch);
                sleep(2);
            }
        };
        #####
        $url     = $this->prepareUrlAndGet();
        $headers = $this->prepareHeaders();
        $cookie  = $this->prepareCookie();
        $fields  = $this->prepareFields();
        do {
            ++$this->attempt;
            #####
            $closeConnection();
            #####
            $max_execution_time = ini_get('max_execution_time');
            $CURLOPT_TIMEOUT    = $max_execution_time / $this->attemptMax - $this->attemptMax;
            #####
            $this->ch = curl_init();
            ##### curl_setopt($this->ch, CURLOPT_USERAGENT, 'VA Services');
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $CURLOPT_TIMEOUT);
            curl_setopt($this->ch, CURLOPT_URL, $url);
            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $this->reqMethod);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, TRUE);
            curl_setopt($this->ch, CURLOPT_MAXREDIRS, 11);
            curl_setopt($this->ch, CURLOPT_HEADER, FALSE);
            curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, [$this, 'callback_CURLOPT_HEADERFUNCTION']);
            ##### POST PUT PATCH
            if (!empty($fields)) {
                if ($this->flagFieldsRaw) {
                    curl_setopt($this->ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
                    //$this->addHeaders(['Content-Type' => 'text/plain',]);
                }
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fields);
            }
            ##### HEADERS
            if (!empty($headers)) {
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
            }
            ##### COOKIE
            if (!empty($cookie)) {
                curl_setopt($this->ch, CURLOPT_COOKIE, $cookie);
            }
            ##### EXECUTION
            $this->respBody = curl_exec($this->ch);
            try {
                $this->respBodyObject = json_decode($this->respBody);
            } catch (Exception $e) {
                $this->respBodyObject = (object)[];
            }
            $this->curlInfo = curl_getinfo($this->ch);
            $this->httpCode = (int)$this->curlInfo['http_code'];
            $errno          = curl_errno($this->ch);
            //if ($errno > 0 || !$this->flagRespSuccess()) {
            if ($errno > 0) {
                $this->respErrno = $errno;
                $this->respErr   = curl_error($this->ch);
            }
            else {
                $closeConnection();
                break;
            }
            $closeConnection();
        } while ($this->attempt < $this->attemptMax);

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
        return $this->reqFields;
        // $reqFields = $this->reqFields;
        // if ($this->flagFieldsRaw) {
        //     return $reqFields;
        // }
        // return http_build_query($reqFields);
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
    public function flagRespSuccess(): bool
    {
        return in_array($this->httpCode, range(200, 299));
    }

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
        $this->respHeaders[] = $str;
        $counter             = count($this->respHeaders);
        $a                   = explode(':', $str, 2);
        if (count($a) === 2) {
            $n = (!empty(trim($a[0]))) ? trim($a[0]) : 'UNDEFINED';
            $v = (!empty(trim($a[1]))) ? trim($a[1]) : 'UNDEFINED';
        }
        else if (count($a) === 1) {
            $n = "_header_$counter";
            $v = (!empty(trim($a[0]))) ? trim($a[0]) : 'UNDEFINED';
        }
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
            case 'respErr':
            case 'respErrno':
            case 'ch':
            case 'methods':
            case 'flagMethodMutator':
            case 'arrUriInterface':
            case 'curlInfo':
            case 'resUrl':
            case 'respBody':
            case 'respBodyObject':
            case 'respHeaders':
            case 'respHeadersStructurized':
            case 'amountLocationsVisited':
            case 'attempt':
            case 'log':
                return $this;
        }

        return $this;
    }

    public function report(): array
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
                'httpCode'                => $this->httpCode,
                'attempt'                 => $this->attempt,
                'amountLocationsVisited'  => $this->amountLocationsVisited,
                'respErrno'               => $this->respErrno,
                'respErr'                 => $this->respErr,
                'flagMethodMutator'       => $this->flagMethodMutator,
                'resUrl'                  => $this->resUrl,
                'respHeaders'             => $this->respHeaders,
                'respHeadersStructurized' => $this->respHeadersStructurized,
                'curlInfo'                => $this->curlInfo,
                'log'                     => $this->log,
                'respBodyObject'          => $this->respBodyObject,
                'respBody'                => $this->respBody,
            ],
        ];
    }
    #endregion Utils
    ##########################################
}
