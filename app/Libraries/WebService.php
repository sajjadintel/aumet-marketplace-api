<?php

namespace App\Libraries;

/**
 * Class WebService
 * @author Ramzi Alqrainy
 * @example
 * $ws = new WebService("http://localhost"); $ws->params['id'] = 10; $ws->params['name'] = "test"; $ws->get()->getResponseAsJson()
 *
 */
class WebService {

    /** @var string $url */
    public $url;

    /** @var array $params */
    public $params = [];

    /** @var  string */
    public $userAgent = 'Aumet 1.0';
    private $_ch;

    /** @var  string */
    private $_response;
    public $proxyAuth;

    /** @var int request timeout in seconds */
    public $timeout = 3;
    public $headers = ['Expect:'];
    public $responseCode;
    public $responseError;

    public function __construct($url) {
        if (empty($url)) {
            throw new \Exception("url is empty! cannot complete your request.");
        }
        $this->url = $url;
        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_DNS_CACHE_TIMEOUT, 0);
    }

    /**
     * @param $name string
     * @param $value string
     * @return $this
     */
    public function addHeader($name, $value) {
        //curl_setopt($this->_ch, CURLOPT_HTTPHEADER, ["$name: $value"]);
        array_push($this->headers, "$name: $value");
        return $this;
    }

    /**
     * Do GET request and passes params array as part of url params
     * @return $this
     */
    public function get($httpBuildQuery = false) {
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($this->params)) {
            if (!$httpBuildQuery) {
                $getFields = "?";
                foreach ($this->params as $key => $value) {
                    $getFields .= "$key=$value&";
                }
                $getFields = rtrim($getFields, "&");
                $this->url .= $getFields;
            } else {
                $this->url .= (strpos($this->url, '?') === false ? '?' : '&')
                        . (is_string($this->params) ? $this->params : http_build_query($this->params));
            }
        }
        $this->request();
        return $this;
    }

    public function downloadFile($saveto) {
        curl_setopt($this->_ch, CURLOPT_HEADER, 0);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, true);

        $this->request();

        if (empty($this->_response))
            return false;

        if (file_exists($saveto)) {
            unlink($saveto);
        }
        $fp = fopen($saveto, 'x');
        fwrite($fp, $this->getResponse());
        fclose($fp);
        return $this;
    }

    /**
     * Do POST request and passes params array as part of the post fields
     * @return $this
     */
    public function post($httpBuildQuery = false) {
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        $postFields = '';
        if (!$httpBuildQuery) {
            foreach ($this->params as $key => $value) {
                $postFields .= "$key=$value&";
            }
            $postFields = rtrim($postFields, "&");
        } else {
            if (!is_string($this->params))
                $postFields = http_build_query($this->params);
            else
                $postFields = $this->params;
        }
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $postFields);

        $this->request();

        return $this;
    }

    /**
     * Do POST request as Json
     * @return $this
     */
    public function jsonPost($json_data, $headers = []) {
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $json_data);

        if (is_array($headers))
            $this->headers = array_merge($this->headers, $headers);
        $this->request();

        return $this;
    }

    /**
     * Do PUT request
     * @return $this
     */
    public function put() {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "PUT");
        $postFields = "";
        foreach ($this->params as $key => $value) {
            $postFields .= "$key=$value&";
        }
        $postFields = rtrim($postFields, "&");
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $postFields);
        $this->request();
        return $this;
    }

    /**
     * Do delete request
     * @return $this
     */
    public function delete() {
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        $getFields = "?";
        foreach ($this->params as $key => $value) {
            $getFields .= "$key=$value&";
        }
        $getFields = rtrim($getFields, "&");
        $this->url .= $getFields;

        $this->request();
        return $this;
    }

    /**
     * Sends the actual request, this method will be called implicitly when calling get() post() delete() and put() methods
     * @throws Exception
     */
    private function request() {
        curl_setopt_array($this->_ch, [
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_FRESH_CONNECT => 0,
            CURLOPT_FORBID_REUSE => 0,
            CURLOPT_BINARYTRANSFER => 1,
        ]);
        if (!empty($this->proxyAuth)) {
            curl_setopt($this->_ch, CURLOPT_USERPWD, $this->proxyAuth);
        }
        curl_setopt($this->_ch, CURLOPT_URL, $this->url);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->timeout);
        $info = curl_getinfo($this->_ch);

        $this->_response = curl_exec($this->_ch);

        if ($this->_response === false) {
            throw new \Exception(curl_errno($this->_ch) . " " . curl_error($this->_ch) . $this->url);
        }
        $this->responseCode = (int) curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
        if ($this->responseCode != 200 && $this->responseCode != 201) {
            $this->responseError = curl_errno($this->_ch) . " " . curl_error($this->_ch);
        }
        curl_close($this->_ch);
    }

    /**
     * Returns response as plain text
     * @return string
     */
    public function getResponse() {
        return $this->_response;
    }

    /**
     * Returns response as json object
     * @param bool $assoc :When TRUE, returned objects will be converted into associative arrays.
     * @return mixed
     */
    public function getResponseAsJsonObject($assoc = false) {
        return json_decode($this->_response, $assoc);
    }

}
