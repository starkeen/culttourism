<?php

declare(strict_types=1);

namespace app\services\openweathermap;

use Cmfcmf\OpenWeatherMap\CurrentWeather;
use DateTime;
use InvalidArgumentException;

class Weather
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

    private CurrentWeather $currentWeather;

    public function __construct(CurrentWeather $currentWeather)
    {
        $this->currentWeather = $currentWeather;
    }

    public function getIcon(): string
    {
        return $this->currentWeather->weather->icon;
    }

    public function getWeatherText(): string
    {
        return self::CODES[$this->currentWeather->weather->id] ?? $this->currentWeather->weather->description;
    }

    public function getWeatherDescription(): string
    {
        return $this->currentWeather->weather->description
            . ', сейчас '
            . $this->currentWeather->clouds->getDescription()
            . $this->formatTemperature(round($this->currentWeather->temperature->now->getValue())). '&deg;C';
    }

    public function getTemperature(): string
    {
        return $this->formatTemperature(round($this->currentWeather->temperature->min->getValue()))
            . '&hellip;'
            . $this->formatTemperature(round($this->currentWeather->temperature->max->getValue()))
            . '°C';
    }

    public function getPressure(): string
    {
        return $this->currentWeather->pressure->getValue() . ' кПа';
    }

    public function getHumidity(): string
    {
        return $this->currentWeather->humidity->getValue() . '%';
    }

    public function getWindDescription(): string
    {
        return sprintf(
            '%s&nbsp;%d&nbsp;м/с',
            $this->getWindDirection($this->currentWeather->wind->direction->getValue()),
            (int) round($this->currentWeather->wind->speed->getValue())
        );
    }

    public function timeUntilSunset(): string
    {
        $tz = $this->currentWeather->city->timezone;
        $sunsetTime = $this->currentWeather->sun->set;
        $sunsetTime->setTimezone($tz);
        $currentTime = new DateTime('now', $tz);
        $timeUntil = $currentTime->diff($sunsetTime);

        if ($timeUntil->invert === 1) {
            return '';
        }

        return sprintf('до заката %d ч ', $timeUntil->h)
            . ($timeUntil->i > 0 ? sprintf('%d мин', $timeUntil->i) : '');
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
