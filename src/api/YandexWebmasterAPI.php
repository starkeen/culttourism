<?php

declare(strict_types=1);

namespace app\api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class YandexWebmasterAPI
{
    private Client $client;

    private string $hostKey = 'https:culttourism.ru:443';

    private ?int $userId = null;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return string|null
     */
    public function getUploadUrl(): ?string
    {
        $url = 'https://api.webmaster.yandex.net/v4/user/'
            . $this->getUserId()
            . '/hosts/' . $this->hostKey
            . '/turbo/uploadAddress';

        $response = $this->client->get(
            $url,
            [
                'headers' => [
                    'Authorization' => 'OAuth ' . YANDEX_WEBMASTER_TOKEN,
                ],
            ]
        );
        $rawData = $response->getBody()->getContents();
        $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);

        return $data['upload_address'] ?? null;
    }

    /**
     * @param string $fileName
     *
     * @return string|null
     */
    public function uploadRSS(string $fileName): ?string
    {
        $uploadUrl = $this->getUploadUrl();

        $body = fopen($fileName, 'rb');
        $response = $this->client->request(
            'POST',
            $uploadUrl,
            [
                'body' => $body,
                'headers' => [
                    'Authorization' => 'OAuth ' . YANDEX_WEBMASTER_TOKEN,
                    'Content-Type' => 'application/rss+xml',
                ],
            ]
        );
        $rawData = $response->getBody()->getContents();
        $data = json_decode($rawData, true);

        return $data['task_id'] ?? null;
    }

    /**
     * @return int
     */
    private function getUserId(): int
    {
        if ($this->userId === null) {
            $this->userId = YANDEX_WEBMASTER_USER_ID;
        }

        return $this->userId;
    }
}
