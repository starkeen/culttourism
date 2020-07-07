<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class PlainClient implements HttpClientInterface
{
    private const SERVICE_URL = 'https://yandex.ru/search/xml';

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $user;

    /**
     * @var string
     */
    private $key;

    public function __construct(ClientInterface $client, string $user, string $key)
    {
        $this->httpClient = $client;
        $this->user = $user;
        $this->key = $key;
    }

    public function fetchResponse(QueryDoc $queryDoc): Result
    {
        $urlParams = [
            'user' => $this->user,
            'key' => $this->key,
            'l10n' => 'ru', // язык уведомлений
            'sortby' => 'rlv',
            'filter' => 'strict', // исключаем взрослый контент
        ];

        $url = self::SERVICE_URL . '?' . http_build_query($urlParams);
        $requestParams = [
            RequestOptions::HEADERS => [
                'Content-Type' => 'text/xml;charset=UTF-8',
                'Content-length'=> $queryDoc->getLength(),
            ],
            RequestOptions::FORCE_IP_RESOLVE => 'v4',
            RequestOptions::BODY => $queryDoc->getBody(),
        ];

        $response = $this->httpClient->request('POST', $url, $requestParams);

        $content = $response->getBody()->getContents();

        return new Result($content);
    }

    public function fetchLimitResponse(): string
    {
        $urlParams = [
            'user' => $this->user,
            'key' => $this->key,
            'action' => 'limits-info',
        ];
        $url = self::SERVICE_URL . '?' . http_build_query($urlParams);
        $requestParams = [
            RequestOptions::HEADERS => [
                'Content-Type' => 'text/xml;charset=UTF-8',
            ],
            RequestOptions::FORCE_IP_RESOLVE => 'v4',
        ];
        $response = $this->httpClient->request('GET', $url, $requestParams);

        return $response->getBody()->getContents();
    }
}
