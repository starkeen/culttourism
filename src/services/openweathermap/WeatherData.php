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
        $this->cityName = $raw['name'];
        $this->temperature = (int) round($raw['main']['temp']);
        $this->temperatureFeels = (int) round($raw['main']['feels_like']);
        if (isset($raw['main']['temp_min'])) {
            $this->temperatureMin = (int) round($raw['main']['temp_min']);
        }
        if (isset($raw['main']['temp_max'])) {
            $this->temperatureMax = (int) round($raw['main']['temp_max']);
        }
        $this->pressure = $raw['main']['pressure'];
        $this->humidity = $raw['main']['humidity'];
        $this->windSpeed = $raw['wind']['speed'];
        $this->windDirection = $raw['wind']['deg'];

        $this->weatherIcon = $raw['weather'][0]['icon'];
        $this->weatherId = $raw['weather'][0]['id'];
        $this->weatherMain = $raw['weather'][0]['main'];
        $this->weatherDescription = $raw['weather'][0]['description'];
    }

    /**
     * @return string
     */
    public function getCityName(): string
    {
        return $this->cityName;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->weatherIcon;
    }

    /**
     * Температура со знаком
     * @return string
     */
    public function getTemperature(): string
    {
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
        return $this->pressure . ' кПа';
    }

    /**
     * @return string
     */
    public function getHumidity(): string
    {
        return $this->humidity . ' %';
    }

    public function getWindDescription(): string
    {
        return sprintf('%s&nbsp;%d&nbsp;м/с', $this->getWindDirection($this->windDirection), (int) round($this->windSpeed));
    }

    public function getWeatherTest(): string
    {
        return self::CODES[$this->weatherId] ?? $this->weatherMain;
    }

    public function getWeatherDescription(): string
    {
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
