<?php

class Curl {
    
    const INTERNAL_ENCODING = 'utf-8';

    private $_cc = null;
    private $_curl = null;
    private $_ttl = 3600; //время жизни кэша в секундах
    private $encoding = 'utf-8';

    public function __construct($db) {
        $this->_cc = new MCurlCache($db);
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->_curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36');
    }

    public function get($url) {
        $text = $this->_cc->get($url);
        if ($text === null) {
            try {
                curl_setopt($this->_curl, CURLOPT_URL, $url);
                curl_setopt($this->_curl, CURLOPT_REFERER, $url);
                $text = curl_exec($this->_curl);
                if ($this->encoding != self::INTERNAL_ENCODING) {
                    $text = mb_convert_encoding($text, self::INTERNAL_ENCODING, $this->encoding);
                }
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $this->_cc->put($url, $text, $this->_ttl);
        }
        return $text;
    }

    public function config($option, $value) {
        curl_setopt($this->_curl, $option, $value);
    }

    public function setTTL($ttl) {
        $this->_ttl = intval($ttl);
    }

    public function setTTLDays($ttl_days) {
        $this->_ttl = 24 * 3600 * intval($ttl_days);
    }
    
    public function setEncoding($encoding) {
        $this->encoding = $encoding;
    }

    public function __destruct() {
        curl_close($this->_curl);
    }

}
