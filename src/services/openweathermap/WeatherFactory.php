<?php

declare(strict_types=1);

namespace app\services\openweathermap;

use Cmfcmf\OpenWeatherMap;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

class WeatherFactory
{
    public static function build(string $key): OpenWeatherMap
    {
        $guzzle = new Client();
        $httpRequestFactory = new HttpFactory();

        return new OpenWeatherMap($key, $guzzle, $httpRequestFactory);
    }
}
