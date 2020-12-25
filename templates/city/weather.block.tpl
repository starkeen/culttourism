<img class="weatherblock_icon" src="/img/weather/{$weather_data.weather_icon}.png" alt="{$weather_data.weather_text}" title="{$weather_data.weather_full}" />
<div class="weatherblock_temperature">{if $weather_data.temp_range}{$weather_data.temp_range}{else}{$weather_data.temperature}{/if}&deg;C</div>
<div class="weatherblock_elements">
    <span title="атмосферное давление">{$weather_data.pressure}&nbsp;кПа</span>,
    <span title="относительная влажность воздуха">{$weather_data.humidity}&nbsp;%</span>,
    <span title="ветер">{$weather_data.winddirect}&nbsp;{$weather_data.windspeed}&nbsp;м/с</span>
</div>
<div class="weatherblock_legal">openweathermap.org</div>