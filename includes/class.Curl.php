<?php

use app\db\MyDB;

class Curl
{
    private const INTERNAL_ENCODING = 'UTF-8';

    private $cc;
    private $curl;
    private $ttl = 3600; //время жизни кэша в секундах
    private $encoding = 'UTF-8';
    private $headers = [];

    /**
     * @param MyDB|null $db
     */
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
    public function get(string $url): ?string
    {
        $text = $this->cc->get($url);
        if ($text === null) {
            curl_setopt($this->curl, CURLOPT_URL, $url);
            curl_setopt($this->curl, CURLOPT_REFERER, $url);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->getHeaders());
            $text = curl_exec($this->curl);
            if ($text === false) {
                return null;
            }
            if ($this->encoding !== self::INTERNAL_ENCODING) {
                $s = str_replace('С?', 'fgr43443443', $text);
                $s = str_replace('Â€', 'â‚¬', $s);
                $s = mb_convert_encoding($s, self::INTERNAL_ENCODING, mb_detect_encoding($text));
                $text = str_replace('fgr43443443', 'ш', $s);
            }
            $this->cc->put($url, $text, $this->ttl);
        }

        return $text;
    }

    /**
     * @param string $url
     * @param array $data
     * @return string
     */
    public function post(string $url, $data = []): ?string
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
            return null;
        }
    }

    /**
     * @param string $header
     * @param $value
     */
    public function addHeader(string $header, $value): void
    {
        $this->headers[$header] = $value;
    }

    /**
     * @return array
     */
    protected function getHeaders(): array
    {
        $out = [];
        foreach ($this->headers as $k => $v) {
            $out[] = $k . ': ' . $v;
        }

        return $out;
    }

    /**
     * @param int $option
     * @param string|int $value
     */
    public function config(int $option, $value): void
    {
        curl_setopt($this->curl, $option, $value);
    }

    public function setTTL(int $ttl): void
    {
        $this->ttl = $ttl;
    }

    public function setTTLDays(int $ttl_days): void
    {
        $this->ttl = 24 * 3600 * $ttl_days;
    }

    public function setEncoding(string $encoding): void
    {
        $this->encoding = $encoding;
    }

    public function __destruct()
    {
        curl_close($this->curl);
    }
}
