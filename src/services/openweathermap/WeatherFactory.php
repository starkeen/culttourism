<?php

declare(strict_types=1);

namespace app\services\openweathermap;

use GuzzleHttp\Client;

class WeatherFactory
{
    public static function build(string $key): WeatherService
    {
        $guzzle = new Client();
        $http = new PlainHttpClient($guzzle);

        return new WeatherService($http, $key);
    }
}
