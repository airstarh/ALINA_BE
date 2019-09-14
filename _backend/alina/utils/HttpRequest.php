<?php

namespace alina\utils;

/**
 * https://www.php.net/manual/ru/function.curl-setopt.php
 */
class HttpRequest
{
    private $requestTYpe       = 'GET';
    public  $reqUri            = '';
    public  $reqGet            = [];
    private $reqPost           = [];
    private $reqPostRaw        = 0;
    private $headersRequest    = [
        //'Content-Type' => 'multipart/form-data; charset=utf-8',
    ];
    private $uriPartsInterface = [
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
    public $resultedUri;
    public $curlInfo;
    public $responseBody;
    public $responseHeaders             = [];
    public $responseHeadersStructurized = [];
    public $redirections                = 0;
    #endregion Response
    ##########################################
    #region Dev Stuff
    public function setReqUri($str)
    {
        $parsedUri = parse_url($str);
        $parsedGet = (isset($parsedUri['query'])) ? $parsedUri['query'] : '';
        $arrGet    = [];
        parse_str($parsedGet, $arrGet);
        $this->uriPartsInterface = array_merge($this->uriPartsInterface, $parsedUri);
        $this->addGet($arrGet);

        #region Clean URI without GET string.
        $this->reqUri = hlpUnParseUri([
            'scheme' => $this->uriPartsInterface['scheme'],
            'host'   => $this->uriPartsInterface['host'],
            'port'   => $this->uriPartsInterface['port'],
            'user'   => $this->uriPartsInterface['user'],
            'pass'   => $this->uriPartsInterface['pass'],
            'path'   => $this->uriPartsInterface['path'],
            // 'query'    => '',
            // 'fragment' => '',
        ]);

        #endregion Clean URI without GET string.
        return $this;
    }

    public function addHeaders($arr)
    {
        $this->headersRequest = array_merge($this->headersRequest, (array)$arr);

        return $this;
    }

    public function addGet($arr)
    {
        $this->reqGet = array_merge($this->reqGet, (array)$arr);

        return $this;
    }

    public function addPost($arr)
    {
        if ($this->reqPostRaw) {
            $this->reqPost = $arr;

            return $this;
        }
        $this->reqPost = array_merge($this->reqPost, (array)$arr);

        return $this;
    }

    public function setPostRaw($v)
    {
        $this->reqPostRaw = $v;

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
            if ($this->reqPostRaw) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        // Set Headers
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $this->responseBody = curl_exec($ch);
        $this->curlInfo     = curl_getinfo($ch);
        curl_close($ch);

        return $this;
    }

    #endregion Dev Stuff
    ##########################################
    #region Final Stuff
    private function forCurlUrlAndGet()
    {
        $arr               = [
            $this->reqUri,
            hlpStrContains($this->reqUri, '?') ? '&' : '?',
            http_build_query($this->reqGet),
        ];
        $s                 = implode('', $arr);
        $this->resultedUri = $s;

        return $this->resultedUri;
    }

    private function forCurlPost()
    {
        $resPost = $this->reqPost;
        if ($this->reqPostRaw) {
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
        $arr = $this->headersRequest;
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
        $counter                 = $this->redirections;
        $this->responseHeaders[] = $str;

        if (empty(trim($str))) {
            $this->redirections++;

            return strlen($str);
        }

        $a                                               = explode(':', $str, 2);
        $n                                               = (isset($a[0])) ? trim($a[0]) : 'UNDEFINED';
        $v                                               = (isset($a[1])) ? trim($a[1]) : 'UNDEFINED';
        $this->responseHeadersStructurized[$counter][$n] = $v;

        return strlen($str);
    }
    #endregion Final Stuff
    ##########################################

}
