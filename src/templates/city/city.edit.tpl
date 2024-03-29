{if $adminlogined}
<div style="border:1px solid #ddd;padding:5px;background-color: #eee;">
    <p>Как можно добавить город в базу</p>
    <form method="get" action="./add/">
        <label for="cityname">название</label>&nbsp;<input type="text" name="cityname" id="cityname" />
        <input type="submit" value="добавить" />
    </form>
</div>
<hr />
{/if}
<h2>Список регионов</h2>
<table style="width:100%;">
    <thead>
        <tr>
            <th>#</th>
            <th>Название</th>
            <th>Описание</th>
            <th>Мета</th>
            <th>Анонс</th>
            <th>Точки</th>
            <th>Фото</th>
            <th>Я.Частота</th>
            <th>Я.Позиция</th>
        </tr>
    </thead>
    <tbody>
        {foreach from=$tcity item=city}
        <tr {if $city.pc_city_id == 0}style="border-top:1px solid #ddd;"{/if}>
            <td>{counter}</td>
            <td><a href="{$city.url}/" title="список достопримечательностей"
                   {if $city.pc_city_id!=0}
                   style="margin-left:30px;"
                   {elseif $city.pc_region_id!=0 && $city.pc_city_id==0 && $city.pc_country_id!=0}
                   style="margin-left:20px;"
                   {/if}
                   {if $city.url=='/goldenring'}
                   style="margin-left:20px;"
                   {/if}
                   ><b>{$city.pc_title}</b></a></td>
            <td style="color: {if $city.len < 1200}#8B0000{else}#228B22{/if};">{$city.len} знаков</td>
            <td>{$city.pc_count_metas}</td>
            <td>{$city.anons_len} зн</td>
            <td>{$city.pc_count_points} точек</td>
            <td>{$city.pc_count_photos} фото</td>
            <td>{$city.ws_weight_max}</td>
            <td style="color:{if $city.ws_position == 0}#8B0000{elseif $city.ws_position > 10}#FF0000{else}#228B22{/if};">{$city.ws_position}</td>
        </tr>
        {/foreach}
    </tbody>
</table>