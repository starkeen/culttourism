<?php

declare(strict_types=1);

namespace app\services\openweathermap;

class WeatherService
{
    private const URL = 'https://api.openweathermap.org/data/2.5/weather';

    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $httpClient;

    private string $appKey;

    public function __construct(HttpClientInterface $httpClient, string $key)
    {
        $this->httpClient = $httpClient;
        $this->appKey = $key;
    }

    public function getWeatherByCoordinates(float $latitude, float $longitude): ?WeatherData
    {
        $url = self::URL . '?' . http_build_query(
            [
                'lat' => $latitude,
                'lon' => $longitude,
                'APPID' => $this->appKey,
                'lang' => 'ru',
                'units' => 'metric',
            ]
        );

        return $this->httpClient->fetchData($url);
    }
}
