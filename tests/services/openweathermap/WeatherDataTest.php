<?php

declare(strict_types=1);

namespace tests\services\openweathermap;

use app\services\openweathermap\WeatherData;
use PHPUnit\Framework\TestCase;

class WeatherDataTest extends TestCase
{
    public function testDataBuilderWithSingleTemperature(): void
    {
        $raw = $this->getRawData();

        $result = new WeatherData($raw);

        self::assertEquals('Town Name', $result->getCityName());
        self::assertEquals('+37&deg;C', $result->getTemperature());
        self::assertEquals('04d', $result->getIcon());
        self::assertEquals('1234 кПа', $result->getPressure());
        self::assertEquals('12%', $result->getHumidity());
        self::assertEquals('ю-в&nbsp;35&nbsp;м/с', $result->getWindDescription());
        self::assertEquals('Временами гроза', $result->getWeatherText());
        self::assertEquals('weather description, по ощущениям +38&deg;C', $result->getWeatherDescription());
    }

    public function testDataBuilderWithTemperatureRange(): void
    {
        $raw = array_merge_recursive($this->getRawData(), [
            'main' => [
                'temp_min' => -23.45,
                'temp_max' => 56.78,
            ],
        ]);

        $result = new WeatherData($raw);

        self::assertEquals('-23&hellip;+57&deg;C', $result->getTemperature());
    }

    private function getRawData(): array
    {
        return [
            'name' => 'Town Name',
            'main' => [
                'temp' => 36.6,
                'feels_like' => 37.7,
                'pressure' => 1234,
                'humidity' => 12,
            ],
            'wind' => [
                'speed' => 34.56,
                'deg' => 123,
            ],
            'weather' => [
                [
                    'id' => 221,
                    'icon' => '04d',
                    'main' => 'main weather data',
                    'description' => 'weather description',
                ],
            ],
        ];
    }
}
