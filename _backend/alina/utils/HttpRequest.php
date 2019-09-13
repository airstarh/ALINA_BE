<?php

namespace alina\utils;

/**
 * https://www.php.net/manual/ru/function.curl-setopt.php
 */
class HttpRequest
{
    /** @var string Can be: GET, POST, PUT, DELETE, OPTION */
    private $requestTYpe = 'GET';
    public $uri         = '';
    /** @var array assoc [$str_key=>$value] */
    public $get = [];
    /** @var array assoc [$str_key=>$value] */
    private $post = [];
    /** @var array assoc [$str_key=>$value] */
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
    public function setUri($str)
    {
        $parsedUri = parse_url($str);
        $parsedGet = (isset($parsedUri['query'])) ? $parsedUri['query'] : '';
        $arrGet    = [];
        parse_str($parsedGet, $arrGet);
        $this->uriPartsInterface = array_merge($this->uriPartsInterface, $parsedUri);
        $this->addGet($arrGet);

        $this->uri = hlpUnParseUri([
            'scheme' => $this->uriPartsInterface['scheme'],
            'host'   => $this->uriPartsInterface['host'],
            'port'   => $this->uriPartsInterface['port'],
            'user'   => $this->uriPartsInterface['user'],
            'pass'   => $this->uriPartsInterface['pass'],
            'path'   => $this->uriPartsInterface['path'],
            // 'query'    => '',
            // 'fragment' => '',
        ]);

        return $this;
    }

    public function addHeaders($arr)
    {
        $this->headersRequest = array_merge($this->headersRequest, (array)$arr);

        return $this;
    }

    public function addGet($arr)
    {
        $this->get = array_merge($this->get, (array)$arr);

        return $this;
    }

    public function addPost($arr)
    {
        $this->post = array_merge($this->post, (array)$arr);

        return $this;
    }

    public function exe()
    {
        $url     = $this->forCurlUrl();
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
    private function forCurlUrl()
    {
        $arr               = [
            $this->uri,
            hlpStrContains($this->uri, '?') ? '&' : '?',
            http_build_query($this->get),
        ];
        $s                 = implode('', $arr);
        $this->resultedUri = $s;

        return $this->resultedUri;
    }

    private function forCurlPost(){
        $resPost = $this->post;
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
