<?php

use GuzzleHttp\Client;

class Bitly
{
    const BITLY_HOST = 'https://api-ssl.bitly.com';
    /** @var \GuzzleHttp\Client */
    private $client;
    /** @var string */
    private $bitlyHost;
    /** @var string */
    private $token = 'cdba9cb93629303877a0e9ae5a33ff0a6877eac5';
    /** @var string */
    private $apiKey = 'R_591b7cdc5de86b5afa99f850f4aa54e0';
    /** @var string */
    private $clientId = '937164071db5a7fab7f82e56aa5198616c96bf37';
    /** @var string */
    private $clientSecret = 'ccd85b27dc6d77ddf409250b5f5f07f8924fdd6b';

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $url
     * @return string
     * @throws RuntimeException
     */
    public function short($url)
    {
        $result = $url;
        $res = $this->client->get($this->buildUrl($url));

        if ($res->getStatusCode() === 200) {
            $response = $res->getBody()->getContents();
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
    private function getToken()
    {
        if (empty($this->token)) {
            $uri = sprintf('/oauth/access_token');
            $res = $this->client->request('POST', self::BITLY_HOST . $uri, [
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
    public function setToken(string $token)
    {
        $this->token = $token;
    }

    /**
     * @param string $host
     */
    public function setHost(string $host)
    {
        $this->bitlyHost = $host;
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function buildUrl($url)
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
    public function getBitlyHost()
    {
        if ($this->bitlyHost === null) {
            $this->bitlyHost = self::BITLY_HOST;
        }

        return $this->bitlyHost;
    }
}