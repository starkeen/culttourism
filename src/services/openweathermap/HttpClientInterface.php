<?php

declare(strict_types=1);

namespace app\services\openweathermap;

interface HttpClientInterface
{
    public function fetchData(string $url): ?WeatherData;
}
