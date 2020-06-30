<?php

declare(strict_types=1);

namespace app\api\yandex_search;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CachedClient implements ClientInterface
{
    /**
     * @var ClientInterface
     */
    private $realClient;

    public function __construct(ClientInterface $client)
    {
        $this->realClient = $client;
    }

    public function send(RequestInterface $request, array $options = []): ResponseInterface
    {
        return $this->realClient->send($request, $options);
    }

    public function sendAsync(RequestInterface $request, array $options = []): PromiseInterface
    {
        return $this->realClient->sendAsync($request, $options);
    }

    public function request($method, $uri, array $options = []): ResponseInterface
    {
        return $this->realClient->request($method, $uri, $options);
    }

    public function requestAsync($method, $uri, array $options = []): PromiseInterface
    {
        return $this->realClient->requestAsync($method, $uri, $options);
    }

    public function getConfig($option = null)
    {
        return $this->realClient->getConfig($option);
    }
}
