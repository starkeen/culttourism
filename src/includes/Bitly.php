<?php

namespace app\includes;

use GuzzleHttp\Client;
use MCurlCache;
use RuntimeException;

class Bitly
{
    private const BITLY_HOST = 'https://api-ssl.bitly.com';
    private const CURL_CACHE_TTL = 86400;

    /** @var Client */
    private $client;

    /** @var MCurlCache */
    private $curlCache;

    /** @var string */
    private $bitlyHost;

    /** @var string */
    private $token = 'cdba9cb93629303877a0e9ae5a33ff0a6877eac5';

    /** @var string */
    private $clientId = '937164071db5a7fab7f82e56aa5198616c96bf37';

    /** @var string */
    private $clientSecret = 'ccd85b27dc6d77ddf409250b5f5f07f8924fdd6b';

    /**
     * @param Client $client
     * @param MCurlCache $cc
     */
    public function __construct(Client $client, MCurlCache $cc)
    {
        $this->client = $client;
        $this->curlCache = $cc;
    }

    /**
     * @param string $url
     * @return string
     * @throws RuntimeException
     */
    public function short(string $url): string
    {
        $result = $url;

        $requestUrl = $this->buildUrl($url);
        $response = $this->curlCache->get($requestUrl);

        if ($response === null) {
            $res = $this->client->get($requestUrl);
            if ($res->getStatusCode() === 200) {
                $response = $res->getBody()->getContents();
                $this->curlCache->put($requestUrl, $response, self::CURL_CACHE_TTL);
            }
        }
        if ((string) $response !== '') {
            $responseData = json_decode($response, true);
            if ((int) $responseData['status_code'] === 200) {
                $result = $responseData['data']['url'];
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    private function getToken(): string
    {
        if (empty($this->token)) {
            $this->client->request('POST', self::BITLY_HOST . '/oauth/access_token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => 200,
            ]);
        }

        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host): void
    {
        $this->bitlyHost = $host;
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function buildUrl($url): string
    {
        $pattern = '%s/v3/shorten?access_token=%s&longUrl=%s&format=json';

        return vsprintf($pattern, [
            $this->getBitlyHost(),
            $this->getToken(),
            urlencode($url),
        ]);
    }

    /**
     * @return string
     */
    public function getBitlyHost(): string
    {
        if ($this->bitlyHost === null) {
            $this->bitlyHost = self::BITLY_HOST;
        }

        return $this->bitlyHost;
    }
}
