<?php

namespace app\includes;

use GuzzleHttp\Client;
use MCurlCache;
use RuntimeException;

class Bitly
{
    private const BITLY_HOST = 'https://api-ssl.bitly.com';
    public const CURL_CACHE_TTL = 86400;

    private Client $client;

    private MCurlCache $curlCache;

    private ?string $bitlyHost = null;

    private ?string $token = BITLY_CLIENT_TOKEN;

    private string $clientId = BITLY_CLIENT_ID;

    private string $clientSecret = BITLY_CLIENT_SECRET;

    /**
     * @param Client     $client
     * @param MCurlCache $cc
     */
    public function __construct(Client $client, MCurlCache $cc)
    {
        $this->client = $client;
        $this->curlCache = $cc;
    }

    /**
     * @param  string $url
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
            $this->client->request(
                'POST',
                self::BITLY_HOST . '/oauth/access_token',
                [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code' => 200, // TODO: use real code
                ]
            );
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

    private function buildUrl(string $url): string
    {
        $pattern = '%s/v3/shorten?access_token=%s&longUrl=%s&format=json';

        return vsprintf(
            $pattern,
            [
            $this->getBitlyHost(),
            $this->getToken(),
            urlencode($url),
            ]
        );
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
