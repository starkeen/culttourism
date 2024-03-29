{if $adminlogined}
<div style="border:1px solid #ddd;padding:5px;background-color: #eee;">
    <p>Здесь можно добавить город в базу</p>
    <form method="get" action="">
        <label for="cityname">название</label>&nbsp;<input type="text" name="cityname" id="cityname" value="{$addregion}" />
        <input type="submit" value="искать" />
    </form>
</div>
{if $already}
<div style="border:1px solid #ddd;padding:5px;background-color: #eee;">
    <p>У нас уже зарегистрированы похожие регионы</p>
    <ul>
        {foreach from=$already item=title key=url}
        <li><a href="{$url}/">{$title}</a></li>
        {/foreach}
    </ul>
</div>
{/if}
{if $inbase}
<div style="border:1px solid #ddd;padding:5px;background-color: #eee;">
    <p>Выберите из справочника</p>
    <table>
        <tr>
            <th>#</th>
            <th>Город</th>
            <th>Регион</th>
            <th>Государство</th>
            <th>Координаты</th>
            <th>&nbsp;</th>
        </tr>
        {foreach from=$inbase item=item}
        <form method="post">
            <tr>
                <td>{counter}.</td>
                <td>
                    {$item.name}
                    <input type="hidden" name="city_name" value="{$item.name}" />
                    <input type="hidden" name="city_id" value="{$item.city_id}" />
                </td>
                <td>
                    {$item.region}
                    <input type="hidden" name="region_name" value="{$item.region}" />
                    <input type="hidden" name="region_id" value="{$item.region_id}" />
                </td>
                <td>
                    {$item.country}
                    <input type="hidden" name="country_name" value="{$item.country}" />
                    <input type="hidden" name="country_id" value="{$item.country_id}" />
                    <input type="hidden" name="country_code" value="{$item.country_code}" />
                </td>
                <td>
                    {$item.latlon}
                    <input type="hidden" name="latitude" value="{$item.lat}" />
                    <input type="hidden" name="longitude" value="{$item.lon}" />
                </td>
                <td>{if $item.pc_title}добавлено: <a href="{$item.url}">{$item.pc_title}</a>{else}<input type="submit" value="добавить" />{/if}</td>
            </tr>
        </form>
        {/foreach}
    </table>
</div>
{/if}

{if $freeplace}
<div style="border:1px solid #ddd;padding:5px;background-color: #eee;">
    <p>Добавление произвольного места</p>
    <form method="post">
        <strong>{$freeplace}</strong>
        <input type="hidden" name="city_name" value="{$freeplace}" />
        <input type="submit" value="добавить" />
    </form>
</div>
{/if}

{else}
<p>Извините, добавление городов и регионов доступно только зарегистрированным пользователям</p>
{/if}
<hr />
