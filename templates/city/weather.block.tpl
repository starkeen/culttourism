<img class="weatherblock_icon"
     src="/img/weather/{$weatherData->getIcon()}.png"
     alt="{$weatherData->getWeatherTest()}"
     title="{$weatherData->getWeatherDescription()}" />
<div class="weatherblock_temperature">
    {$weatherData->getTemperature()}
</div>
<div class="weatherblock_elements">
    <span title="атмосферное давление">{$weatherData->getPressure()}</span>,
    <span title="относительная влажность воздуха">{$weatherData->getHumidity()}</span>,
    <span title="ветер">{$weatherData->getWindDescription()}</span>
</div>
<div class="weatherblock_legal">openweathermap.org</div>
