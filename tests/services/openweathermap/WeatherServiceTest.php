<?php

declare(strict_types=1);

namespace tests\services\openweathermap;

use app\services\openweathermap\HttpClientInterface;
use app\services\openweathermap\WeatherData;
use app\services\openweathermap\WeatherService;
use PHPUnit\Framework\TestCase;

class WeatherServiceTest extends TestCase
{
    public function testRequest(): void
    {
        $http = $this->getMockBuilder(HttpClientInterface::class)->onlyMethods(['fetchData'])->getMock();
        $resultMock = $this->getMockBuilder(WeatherData::class)->disableOriginalConstructor()->getMock();

        $service = new WeatherService($http, 'test_key_example');

        $http->expects(self::once())
            ->method('fetchData')
            ->with('https://api.openweathermap.org/data/2.5/weather?lat=12.34&lon=56.78&APPID=test_key_example&lang=ru&units=metric')
            ->willReturn($resultMock);

        $result = $service->getWeatherByCoordinates(12.34, 56.78);

        self::assertEquals($resultMock, $result);
    }
}
