<?php

namespace app\includes;

use GuzzleHttp\Client;
use RuntimeException;

class ReCaptcha
{
    const KEY = '6LcLZRoUAAAAADiMQC7i3obCBBRkKJZihgJZx2cV';
    const SECRET = '6LcLZRoUAAAAAGXgjcBiEFGtfKPJODLsInmOqNxT';
    const URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var Client */
    private $httpClient;

    public function __construct(Client $client)
    {
        $this->httpClient = $client;
    }

    /**
     * @param string $token
     *
     * @return boolean
     * @throws RuntimeException
     */
    public function check($token): bool
    {
        try {
            $response = $this->httpClient->post(self::URL, $this->getRequestData($token));
            $content = $response->getBody()->getContents();
            $answer = json_decode($content, true);
        } catch (RuntimeException $exception) {
            $answer = null;
        }

        return (bool) $answer['success'] ?? false;
    }

    /**
     * @param string $token
     *
     * @return array
     */
    private function getRequestData($token): array
    {
        return [
            'form_params' => [
                'secret' => self::SECRET,
                'response' => $token,
                'remoteip' => $_SERVER['REMOTE_ADDR'],
            ],
        ];
    }
}
