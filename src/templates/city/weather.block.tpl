<img class="weatherblock_icon"
     src="/img/weather/{$weatherData->getIcon()}.png"
     alt="{$weatherData->getWeatherText()}"
     title="{$weatherData->getWeatherDescription()}" />
<div class="weatherblock_temperature">
    {$weatherData->getTemperature()}
</div>
<div class="weatherblock_elements">
    <span title="атмосферное давление">{$weatherData->getPressure()}</span>,
    <span title="относительная влажность воздуха">{$weatherData->getHumidity()}</span>,
    <span title="ветер">{$weatherData->getWindDescription()}</span>
    <span style="padding-right: 18px;">{$weatherData->timeUntilSunset()}</span>
</div>
<div class="weatherblock_legal">openweathermap.org</div>
