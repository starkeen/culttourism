<?php

namespace app\includes;

use GuzzleHttp\Client;
use Throwable;

class ReCaptcha
{
    private const URL = 'https://www.google.com/recaptcha/api/siteverify';

    private Client $httpClient;

    private string $key;
    private string $secret;

    public function __construct(Client $client, string $key, string $secret)
    {
        $this->httpClient = $client;
        $this->key = $key;
        $this->secret = $secret;
    }

    /**
     * @param string $token
     *
     * @return boolean
     */
    public function check(string $token): bool
    {
        try {
            $response = $this->httpClient->post(self::URL, $this->getRequestData($token));
            $content = $response->getBody()->getContents();
            $answer = json_decode($content, true);
        } catch (Throwable $exception) {
            $answer = null;
        }

        return (bool) ($answer['success'] ?? false);
    }

    /**
     * @param string $token
     *
     * @return array
     */
    private function getRequestData(string $token): array
    {
        return [
            'form_params' => [
                'secret' => $this->secret,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ],
        ];
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
