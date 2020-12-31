<?php

declare(strict_types=1);

namespace app\services\openweathermap;

use GuzzleHttp\Client;

class PlainHttpClient implements HttpClientInterface
{
    private Client $guzzle;

    public function __construct(Client $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    public function fetchData(string $url): ?WeatherData
    {
        $result = null;

        $response = $this->guzzle->get($url);
        if ($response->getStatusCode() === 200) {
            $rawData = $response->getBody()->getContents();
            $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);
            $result = new WeatherData($data);
        }

        return $result;
    }
}
