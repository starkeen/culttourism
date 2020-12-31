<?php

declare(strict_types=1);

namespace app\services\openweathermap;

use InvalidArgumentException;

class WeatherData
{
    private const CODES = [
        200 => 'Гроза с дождем',
        201 => 'Дождь с грозой',
        202 => 'Сильный дождь с грозой',
        210 => 'Небольшая гроза',
        211 => 'Гроза',
        212 => 'Сильная гроза',
        221 => 'Временами гроза',
        230 => 'Гроза с дождем',
        231 => 'Гроза с дождем',
        232 => 'Гроза с ливнем',
        300 => 'Небольшая изморось',
        301 => 'Изморось',
        302 => 'Изморось',
        310 => 'Изморось, дождь',
        311 => 'Моросящий дождь',
        312 => 'Сильный дождь',
        321 => 'Ливень',
        500 => 'Небольшой дождь',
        501 => 'Умеренный дождь',
        502 => 'Сильный дождь',
        503 => 'Очень сильный дождь',
        504 => 'Крайне сильный дождь',
        511 => 'Ледяной дождь',
        520 => 'Небольшой дождь',
        521 => 'Дождь',
        522 => 'Ливень',
        600 => 'Небольшой снег',
        601 => 'Снег',
        602 => 'Сильный снег',
        611 => 'Дождь со снегом',
        621 => 'Снегопад',
        700 => 'Дымка',
        701 => 'Туман',
        711 => 'Дым',
        721 => 'Мгла',
        731 => 'Пыльная буря',
        741 => 'Сильный туман',
        800 => 'Ясно',
        801 => 'Небольшая облачность',
        802 => 'Переменная облачность',
        803 => 'Переменная облачность',
        804 => 'Пасмурно',
        900 => 'Торнадо',
        901 => 'Тропический шторм',
        902 => 'Ураган',
        903 => 'Холодно',
        904 => 'Жарко',
        905 => 'Ветрено',
        906 => 'Град',
    ];

    private array $rawData;
    private bool $parsed;

    private string $cityName;
    private string $weatherIcon;
    private int $temperature;
    private int $temperatureFeels;
    private ?int $temperatureMin;
    private ?int $temperatureMax;
    private int $pressure;
    private int $humidity;
    private float $windSpeed;
    private int $windDirection;
    private string $weatherMain;
    private string $weatherDescription;
    private int $weatherId;

    public function __construct(array $raw)
    {
        $this->rawData = $raw;
        $this->parsed = false;
    }
    
    private function parse(): void
    {
        if (!$this->parsed) {
            $this->cityName = $this->rawData['name'];
            $this->temperature = (int) round($this->rawData['main']['temp']);
            $this->temperatureFeels = (int) round($this->rawData['main']['feels_like']);
            $this->temperatureMin = isset($this->rawData['main']['temp_min']) ? (int) round($this->rawData['main']['temp_min']) : null;
            $this->temperatureMax = isset($this->rawData['main']['temp_max']) ? (int) round($this->rawData['main']['temp_max']) : null;
            $this->pressure = $this->rawData['main']['pressure'];
            $this->humidity = $this->rawData['main']['humidity'];
            $this->windSpeed = $this->rawData['wind']['speed'];
            $this->windDirection = $this->rawData['wind']['deg'];

            $this->weatherIcon = $this->rawData['weather'][0]['icon'];
            $this->weatherId = $this->rawData['weather'][0]['id'];
            $this->weatherMain = $this->rawData['weather'][0]['main'];
            $this->weatherDescription = $this->rawData['weather'][0]['description'];

            $this->parsed = true;
        }
    }

    /**
     * @return string
     */
    public function getCityName(): string
    {
        $this->parse();
        return $this->cityName;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        $this->parse();
        return $this->weatherIcon;
    }

    /**
     * Температура со знаком
     * @return string
     */
    public function getTemperature(): string
    {
        $this->parse();
        $result = null;
        if ($this->temperatureMin !== null
            && $this->temperatureMax !== null
            && $this->temperatureMin !== $this->temperatureMax
        ) {
            $result = $this->formatTemperature($this->temperatureMin)
                . '&hellip;'
                . $this->formatTemperature($this->temperatureMax);
        } else {
            $result = $this->formatTemperature($this->temperature);
        }

        return $result . '&deg;C';
    }

    /**
     * @return string
     */
    public function getPressure(): string
    {
        $this->parse();
        return $this->pressure . ' кПа';
    }

    /**
     * @return string
     */
    public function getHumidity(): string
    {
        $this->parse();
        return $this->humidity . '%';
    }

    public function getWindDescription(): string
    {
        $this->parse();
        return sprintf('%s&nbsp;%d&nbsp;м/с', $this->getWindDirection($this->windDirection), (int) round($this->windSpeed));
    }

    public function getWeatherText(): string
    {
        $this->parse();
        return self::CODES[$this->weatherId] ?? $this->weatherMain;
    }

    public function getWeatherDescription(): string
    {
        $this->parse();
        return $this->weatherDescription
            . ', по ощущениям '
            . $this->formatTemperature($this->temperatureFeels)
            . '&deg;C';
    }

    private function formatTemperature(float $temperature): string
    {
        return $temperature > 0 ? '+' . $temperature : (string) $temperature;
    }

    private function getWindDirection(float $degree): string
    {
        if ($degree < 0) {
            throw new InvalidArgumentException('Неправильное направление ветра');
        }

        if ($degree < 22.5) {
            $result = 'сев';
        } elseif ($degree < 67.5) {
            $result = 'с-в';
        } elseif ($degree < 112.5) {
            $result = 'вост';
        } elseif ($degree < 157.5) {
            $result = 'ю-в';
        } elseif ($degree < 202.5) {
            $result = 'юж';
        } elseif ($degree < 247.5) {
            $result = 'ю-3';
        } elseif ($degree < 292.5) {
            $result = 'зап';
        } elseif ($degree < 337.5) {
            $result = 'с-з';
        } else {
            $result = 'сев';
        }

        return $result;
    }
}
