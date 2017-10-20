<?php

use app\db\MyDB;

class Curl
{
    const INTERNAL_ENCODING = 'utf-8';

    private $cc = null;
    private $curl = null;
    private $ttl = 3600; //время жизни кэша в секундах
    private $encoding = 'utf-8';
    private $headers = [];

    public function __construct(MyDB $db = null)
    {
        if ($db) {
            $this->cc = new MCurlCache($db);
        }

        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt(
            $this->curl,
            CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.101 Safari/537.36'
        );
    }

    /**
     *
     * @param string $url
     *
     * @return string
     */
    public function get($url)
    {
        $text = $this->cc->get($url);
        if ($text === null) {
            try {
                curl_setopt($this->curl, CURLOPT_URL, $url);
                curl_setopt($this->curl, CURLOPT_REFERER, $url);
                curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
                $text = curl_exec($this->curl);
                if ($this->encoding !== self::INTERNAL_ENCODING) {
                    $text = mb_convert_encoding($text, self::INTERNAL_ENCODING, $this->encoding);
                    $text = iconv($this->encoding, self::INTERNAL_ENCODING . '//IGNORE', $text);
                }
            } catch (Exception $e) {
                //echo $e->getMessage();
            }
            $this->cc->put($url, $text, $this->ttl);
        }
        return $text;
    }

    public function post($url, $data = [])
    {
        try {
            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
            $text = curl_exec($this->curl);
            if ($text === false) {
                throw new RuntimeException(curl_error($this->curl));
            }
            $this->cc->put($url . '?' . $data, $text, $this->ttl);
            return $text;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function addHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    protected function getHeaders()
    {
        $out = [];
        foreach ($this->headers as $k => $v) {
            $out[] = $k . ': ' . $v;
        }
        return $out;
    }

    public function config($option, $value)
    {
        curl_setopt($this->curl, $option, $value);
    }

    public function setTTL($ttl)
    {
        $this->ttl = intval($ttl);
    }

    public function setTTLDays($ttl_days)
    {
        $this->ttl = 24 * 3600 * intval($ttl_days);
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }

}
