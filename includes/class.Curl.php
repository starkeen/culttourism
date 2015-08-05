<?php

class Curl {

    private $_cc = null;
    private $_curl = null;

    public function __construct($db) {
        $this->_cc = new MCurlCache($db);
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36');
    }

    public function get($url) {
        $text = $this->_cc->get($url);
        if ($text === null) {
            try {
                curl_setopt($this->_curl, CURLOPT_URL, $url);
                curl_setopt($this->_curl, CURLOPT_REFERER, $url);
                $text = curl_exec($this->_curl);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $this->_cc->put($url, $text);
        }
        return $text;
    }

    public function config($option, $value) {
        curl_setopt($this->_curl, $option, $value);
    }

    public function __destruct() {
        curl_close($this->_curl);
    }

}
