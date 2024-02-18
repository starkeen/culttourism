<?php

declare(strict_types=1);

namespace app\services\shortio;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Throwable;

class ShortIoClient
{
    private Client $client;

    private string $domain;

    private string $token;

    public function __construct(Client $client, string $domain, string $token)
    {
        $this->client = $client;
        $this->domain = $domain;
        $this->token = $token;
    }

    public function short(string $url): string
    {
        try {
            $requestData = [
                RequestOptions::HEADERS => [
                    'Authorization' => $this->getToken(),
                    'accept' => 'application/json',
                    'content-type' => 'application/json',
                ],
                RequestOptions::JSON => [
                    'domain' => $this->getDomain(),
                    'originalURL' => $url,
                ],
            ];
            $response = $this->client->post('https://api.short.io/links', $requestData);
            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $result = $data['shortURL'];
        } catch (ClientException $exception) {
            throw new ShortIoException('Short.io request error', 500, $exception);
        } catch (Throwable $exception) {
            throw new ShortIoException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $result;
    }

    private function getToken(): string
    {
        return $this->token;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }
}
